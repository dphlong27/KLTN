<?php

namespace App\Services;

use App\Models\CongTy;
use App\Models\HoSo;
use App\Models\NguoiDung;
use App\Models\SmartJobAlert;
use App\Models\TinTuyenDung;
use Illuminate\Support\Str;

class SmartJobAlertService
{
    private const MIN_ALERT_SCORE = 50.0;

    public function __construct(private readonly AppNotificationService $notificationService)
    {
    }

    /**
     * @return array<int>
     */
    public function generateForActivatedJob(TinTuyenDung $job, string $activityType = 'published'): array
    {
        $job->loadMissing([
            'congTy.nguoiDungTheoDois',
            'nganhNghes:id,ten_nganh',
            'kyNangYeuCaus.kyNang:id,ten_ky_nang',
            'parsing:id,tin_tuyen_dung_id,parsed_skills_json,parsed_requirements_json',
        ]);

        $company = $job->congTy;
        if (!$company instanceof CongTy) {
            return [];
        }

        $activityAt = ($activityType === 'reopened' ? $job->reactivated_at : ($job->published_at ?? $job->created_at)) ?? now();

        $followers = $company->nguoiDungTheoDois()
            ->where('nguoi_dungs.vai_tro', NguoiDung::VAI_TRO_UNG_VIEN)
            ->where('nguoi_dungs.trang_thai', 1)
            ->wherePivot('created_at', '<=', $activityAt)
            ->with([
                'hoSos' => fn ($query) => $query
                    ->with('parsing:id,ho_so_id,parsed_skills_json,parsed_experience_json,parsed_education_json')
                    ->latest(),
                'kyNangs:id,ten_ky_nang',
            ])
            ->get();

        $notifiedRecipientIds = [];

        foreach ($followers as $candidate) {
            $match = $this->bestMatchForCandidate($candidate, $job);
            if (($match['score'] ?? 0) < self::MIN_ALERT_SCORE) {
                continue;
            }

            $alert = SmartJobAlert::updateOrCreate(
                [
                    'nguoi_dung_id' => (int) $candidate->id,
                    'tin_tuyen_dung_id' => (int) $job->id,
                ],
                [
                    'cong_ty_id' => (int) $company->id,
                    'ho_so_id' => $match['profile_id'],
                    'match_score' => $match['score'],
                    'match_level' => $this->matchLevel($match['score']),
                    'matched_skills_json' => $match['matched_skills'],
                    'missing_skills_json' => $match['missing_skills'],
                    'matched_industries_json' => $match['matched_industries'],
                    'reasons_json' => $match['reasons'],
                    'metadata_json' => [
                        'activity_type' => $activityType,
                        'activity_at' => $activityAt->toISOString(),
                        'score_breakdown' => $match['score_breakdown'],
                        'job_required_skills' => $match['job_required_skills'],
                        'candidate_profile_title' => $match['profile_title'],
                    ],
                    'trang_thai' => SmartJobAlert::TRANG_THAI_MOI,
                    'notified_at' => now(),
                    'read_at' => null,
                    'dismissed_at' => null,
                ],
            );

            $this->notifyCandidate($candidate, $job, $company, $alert, $activityType);
            $notifiedRecipientIds[] = (int) $candidate->id;
        }

        return collect($notifiedRecipientIds)->unique()->values()->all();
    }

    public function bestMatchForCandidate(NguoiDung $candidate, TinTuyenDung $job): array
    {
        $jobProfile = $this->buildJobProfile($job);
        $profiles = $candidate->relationLoaded('hoSos') ? $candidate->hoSos : $candidate->hoSos()->with('parsing')->latest()->get();

        if ($profiles->isEmpty()) {
            return $this->scoreProfile($candidate, null, $job, $jobProfile);
        }

        return $profiles
            ->map(fn (HoSo $profile) => $this->scoreProfile($candidate, $profile, $job, $jobProfile))
            ->sortByDesc('score')
            ->values()
            ->first();
    }

    public function mapAlert(SmartJobAlert $alert): array
    {
        $job = $alert->tinTuyenDung;
        $company = $alert->congTy ?: $job?->congTy;

        return [
            'id' => $alert->id,
            'match_score' => round((float) $alert->match_score, 1),
            'match_level' => $alert->match_level,
            'trang_thai' => $alert->trang_thai,
            'matched_skills' => $alert->matched_skills_json ?: [],
            'missing_skills' => $alert->missing_skills_json ?: [],
            'matched_industries' => $alert->matched_industries_json ?: [],
            'reasons' => $alert->reasons_json ?: [],
            'metadata' => $alert->metadata_json ?: [],
            'notified_at' => optional($alert->notified_at)->toISOString(),
            'read_at' => optional($alert->read_at)->toISOString(),
            'dismissed_at' => optional($alert->dismissed_at)->toISOString(),
            'created_at' => optional($alert->created_at)->toISOString(),
            'profile' => $alert->hoSo ? [
                'id' => $alert->hoSo->id,
                'tieu_de_ho_so' => $alert->hoSo->tieu_de_ho_so,
                'vi_tri_ung_tuyen_muc_tieu' => $alert->hoSo->vi_tri_ung_tuyen_muc_tieu,
            ] : null,
            'job' => $job ? [
                'id' => $job->id,
                'tieu_de' => $job->tieu_de,
                'dia_diem_lam_viec' => $job->dia_diem_lam_viec,
                'hinh_thuc_lam_viec' => $job->hinh_thuc_lam_viec,
                'muc_luong' => $job->muc_luong,
                'muc_luong_tu' => $job->muc_luong_tu,
                'muc_luong_den' => $job->muc_luong_den,
                'ngay_het_han' => optional($job->ngay_het_han)->toISOString(),
                'published_at' => optional($job->published_at)->toISOString(),
                'reactivated_at' => optional($job->reactivated_at)->toISOString(),
            ] : null,
            'company' => $company ? [
                'id' => $company->id,
                'ten_cong_ty' => $company->ten_cong_ty,
                'logo_url' => $company->logo
                    ? url('/api/v1/cong-ty-logo?path=' . urlencode($company->logo))
                    : null,
            ] : null,
        ];
    }

    private function scoreProfile(NguoiDung $candidate, ?HoSo $profile, TinTuyenDung $job, array $jobProfile): array
    {
        $candidateProfile = $this->buildCandidateProfile($candidate, $profile);
        $requiredSkills = $jobProfile['skills'];
        $candidateSkills = $candidateProfile['skills'];
        $matchedSkills = $this->intersectByNormalizedName($candidateSkills, $requiredSkills);
        $missingSkills = $this->diffByNormalizedName($requiredSkills, $candidateSkills);

        $skillScore = count($requiredSkills) > 0
            ? min(45, (count($matchedSkills) / count($requiredSkills)) * 45)
            : 18;

        $matchedIndustries = $this->intersectByNormalizedName($candidateProfile['industries'], $jobProfile['industries']);
        $industryScore = $matchedIndustries ? 15 : $this->textOverlapScore($candidateProfile['industry_text'], $jobProfile['industry_text'], 10);
        $titleScore = $this->textOverlapScore($candidateProfile['title_text'], $jobProfile['title_text'], 15);
        $experienceScore = $this->experienceScore((int) ($candidateProfile['years_experience'] ?? 0), (string) ($job->kinh_nghiem_yeu_cau ?? ''));
        $educationScore = $this->educationScore((string) ($profile?->trinh_do ?? ''), (string) ($job->trinh_do_yeu_cau ?? ''));
        $locationScore = $this->textOverlapScore((string) ($profile?->dia_chi ?? $candidate->dia_chi ?? ''), (string) ($job->dia_diem_lam_viec ?? ''), 5);

        $score = round(min(100, $skillScore + $industryScore + $titleScore + $experienceScore + $educationScore + $locationScore), 1);

        return [
            'profile_id' => $profile?->id,
            'profile_title' => $profile?->tieu_de_ho_so,
            'score' => $score,
            'matched_skills' => array_slice($matchedSkills, 0, 12),
            'missing_skills' => array_slice($missingSkills, 0, 8),
            'matched_industries' => array_slice($matchedIndustries, 0, 5),
            'job_required_skills' => array_slice($requiredSkills, 0, 20),
            'score_breakdown' => [
                'skills' => round($skillScore, 1),
                'industry' => round($industryScore, 1),
                'title' => round($titleScore, 1),
                'experience' => round($experienceScore, 1),
                'education' => round($educationScore, 1),
                'location' => round($locationScore, 1),
            ],
            'reasons' => $this->buildReasons($score, $matchedSkills, $missingSkills, $matchedIndustries, $titleScore, $experienceScore),
        ];
    }

    private function buildJobProfile(TinTuyenDung $job): array
    {
        $skills = collect($job->kyNangYeuCaus ?? [])
            ->map(fn ($item) => $item->kyNang?->ten_ky_nang)
            ->filter()
            ->values()
            ->all();

        $parsedSkills = $this->flattenNameList($job->parsing?->parsed_skills_json ?? []);
        $industries = collect($job->nganhNghes ?? [])->map(fn ($industry) => $industry->ten_nganh)->filter()->values()->all();

        return [
            'skills' => $this->uniqueNames([...$skills, ...$parsedSkills]),
            'industries' => $this->uniqueNames($industries),
            'title_text' => implode(' ', [$job->tieu_de, $job->cap_bac, $job->mo_ta_cong_viec]),
            'industry_text' => implode(' ', $industries),
        ];
    }

    private function buildCandidateProfile(NguoiDung $candidate, ?HoSo $profile): array
    {
        $userSkills = $candidate->relationLoaded('kyNangs')
            ? $candidate->kyNangs->pluck('ten_ky_nang')->all()
            : $candidate->kyNangs()->pluck('ten_ky_nang')->all();

        $profileSkills = $this->flattenNameList($profile?->ky_nang_json ?? []);
        $parsedSkills = $this->flattenNameList($profile?->parsing?->parsed_skills_json ?? []);
        $targetIndustry = $profile?->ten_nganh_nghe_muc_tieu ? [$profile->ten_nganh_nghe_muc_tieu] : [];

        return [
            'skills' => $this->uniqueNames([...$userSkills, ...$profileSkills, ...$parsedSkills]),
            'industries' => $this->uniqueNames($targetIndustry),
            'industry_text' => (string) ($profile?->ten_nganh_nghe_muc_tieu ?? ''),
            'title_text' => implode(' ', [
                $profile?->tieu_de_ho_so,
                $profile?->vi_tri_ung_tuyen_muc_tieu,
                $profile?->muc_tieu_nghe_nghiep,
                $profile?->mo_ta_ban_than,
            ]),
            'years_experience' => $profile?->kinh_nghiem_nam ?? 0,
        ];
    }

    private function buildReasons(float $score, array $matchedSkills, array $missingSkills, array $matchedIndustries, float $titleScore, float $experienceScore): array
    {
        $reasons = [];

        if ($matchedSkills) {
            $reasons[] = 'Khớp kỹ năng: ' . implode(', ', array_slice($matchedSkills, 0, 4));
        }

        if ($matchedIndustries) {
            $reasons[] = 'Cùng định hướng ngành: ' . implode(', ', array_slice($matchedIndustries, 0, 2));
        }

        if ($titleScore >= 8) {
            $reasons[] = 'Vị trí mục tiêu/CV gần với tiêu đề tin tuyển dụng.';
        }

        if ($experienceScore >= 10) {
            $reasons[] = 'Kinh nghiệm hiện tại phù hợp yêu cầu.';
        }

        if ($missingSkills) {
            $reasons[] = 'Có thể bổ sung thêm: ' . implode(', ', array_slice($missingSkills, 0, 3));
        }

        if (!$reasons) {
            $reasons[] = $score >= 65
                ? 'Hồ sơ có nhiều tín hiệu phù hợp với tin tuyển dụng này.'
                : 'Tin tuyển dụng có một số điểm phù hợp với hồ sơ của bạn.';
        }

        return array_slice($reasons, 0, 5);
    }

    private function notifyCandidate(NguoiDung $candidate, TinTuyenDung $job, CongTy $company, SmartJobAlert $alert, string $activityType): void
    {
        $score = round((float) $alert->match_score);
        $verb = $activityType === 'reopened' ? 'mở lại' : 'đăng';
        $title = $score >= 75 ? "Job mới rất hợp với bạn ({$score}%)" : "Job mới phù hợp với bạn ({$score}%)";
        $message = "{$company->ten_cong_ty} vừa {$verb} vị trí {$job->tieu_de}. " . (($alert->reasons_json[0] ?? null) ?: 'Hồ sơ của bạn có tín hiệu phù hợp.');

        $this->notificationService->createForUser(
            $candidate,
            'smart_job_alert',
            $title,
            $message,
            "/jobs/{$job->id}",
            [
                'source' => 'smart_job_alert',
                'smart_alert_id' => $alert->id,
                'match_score' => $alert->match_score,
                'match_level' => $alert->match_level,
                'reasons' => $alert->reasons_json ?: [],
                'company' => [
                    'id' => $company->id,
                    'name' => $company->ten_cong_ty,
                ],
                'job' => [
                    'id' => $job->id,
                    'title' => $job->tieu_de,
                ],
            ],
        );
    }

    private function matchLevel(float $score): string
    {
        return match (true) {
            $score >= 80 => 'excellent',
            $score >= 65 => 'strong',
            $score >= 50 => 'good',
            default => 'medium',
        };
    }

    private function experienceScore(int $candidateYears, string $requiredText): float
    {
        $requiredYears = $this->extractFirstNumber($requiredText);
        if ($requiredYears === null) {
            return 8;
        }

        if ($candidateYears >= $requiredYears) {
            return 15;
        }

        if ($candidateYears + 1 >= $requiredYears) {
            return 10;
        }

        return max(0, 6 - (($requiredYears - $candidateYears) * 2));
    }

    private function educationScore(string $candidateEducation, string $requiredEducation): float
    {
        if ($candidateEducation === '' || $requiredEducation === '') {
            return 3;
        }

        return $this->normalize($candidateEducation) === $this->normalize($requiredEducation)
            ? 5
            : $this->textOverlapScore($candidateEducation, $requiredEducation, 3);
    }

    private function textOverlapScore(string $candidateText, string $jobText, float $maxScore): float
    {
        $candidateWords = $this->keywordSet($candidateText);
        $jobWords = $this->keywordSet($jobText);

        if (!$candidateWords || !$jobWords) {
            return 0;
        }

        $overlap = array_intersect($candidateWords, $jobWords);

        return min($maxScore, (count($overlap) / max(1, min(count($candidateWords), count($jobWords)))) * $maxScore);
    }

    private function intersectByNormalizedName(array $left, array $right): array
    {
        $rightMap = collect($right)->mapWithKeys(fn ($name) => [$this->normalize($name) => $name])->all();

        return collect($left)
            ->filter(fn ($name) => isset($rightMap[$this->normalize($name)]))
            ->values()
            ->unique()
            ->all();
    }

    private function diffByNormalizedName(array $required, array $candidate): array
    {
        $candidateMap = collect($candidate)->mapWithKeys(fn ($name) => [$this->normalize($name) => true])->all();

        return collect($required)
            ->filter(fn ($name) => !isset($candidateMap[$this->normalize($name)]))
            ->values()
            ->unique()
            ->all();
    }

    private function flattenNameList(mixed $items): array
    {
        if (!is_array($items)) {
            return [];
        }

        return collect($items)
            ->flatMap(function ($item) {
                if (is_string($item)) {
                    return [$item];
                }

                if (!is_array($item)) {
                    return [];
                }

                $name = $item['name']
                    ?? $item['ten_ky_nang']
                    ?? $item['skill']
                    ?? $item['ky_nang']
                    ?? $item['title']
                    ?? null;

                return $name ? [$name] : $this->flattenNameList($item);
            })
            ->filter()
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->values()
            ->all();
    }

    private function uniqueNames(array $names): array
    {
        return collect($names)
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->unique(fn ($name) => $this->normalize($name))
            ->values()
            ->all();
    }

    private function keywordSet(string $text): array
    {
        $normalized = $this->normalize($text);
        $words = preg_split('/\s+/', $normalized) ?: [];
        $stopWords = ['va', 'voi', 'cho', 'cac', 'the', 'and', 'or', 'of', 'in', 'to', 'a', 'an', 'la', 'tai', 'co'];

        return collect($words)
            ->filter(fn ($word) => mb_strlen($word) >= 3 && !in_array($word, $stopWords, true))
            ->unique()
            ->values()
            ->all();
    }

    private function extractFirstNumber(string $text): ?int
    {
        if (preg_match('/(\d+)/', $text, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    private function normalize(string $value): string
    {
        $value = Str::lower(trim($value));
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        $value = $converted !== false ? $converted : $value;
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?? $value;

        return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
    }
}
