<?php

namespace App\Http\Controllers\Api;

use App\Events\ApplicationChanged;
use App\Http\Controllers\Api\Concerns\ResolvesEmployerCompany;
use App\Http\Controllers\Controller;
use App\Http\Requests\UngTuyen\CapNhatTrangThaiRequest;
use App\Models\CongTy;
use App\Models\UngTuyen;
use App\Notifications\ApplicationStatusNotification;
use App\Notifications\InterviewScheduledNotification;
use App\Services\Ai\AiClientService;
use App\Services\AppNotificationService;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class NhaTuyenDungUngTuyenController extends Controller
{
    use ResolvesEmployerCompany;

    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly AppNotificationService $appNotificationService,
        private readonly AiClientService $aiClientService,
    ) {
    }

    private function applicationAuditSnapshot(UngTuyen $ungTuyen): array
    {
        return $ungTuyen->only([
            'id',
            'tin_tuyen_dung_id',
            'ho_so_id',
            'trang_thai',
            'hr_phu_trach_id',
            'ngay_hen_phong_van',
            'hinh_thuc_phong_van',
            'nguoi_phong_van',
            'link_phong_van',
            'ket_qua_phong_van',
            'trang_thai_tham_gia_phong_van',
            'rubric_danh_gia_phong_van',
            'da_rut_don',
        ]);
    }

    private function broadcastApplicationChanged(UngTuyen $application, string $changeType, array $payload = []): void
    {
        $event = ApplicationChanged::fromApplication($application, $changeType, $payload);

        if (!$event) {
            return;
        }

        try {
            broadcast($event);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    private function resolveValidHrPhuTrachId(?int $memberId, CongTy $congTy, int $fallbackUserId): int
    {
        if (!$memberId) {
            return $fallbackUserId;
        }

        $exists = $congTy->thanhViens()
            ->where('nguoi_dungs.id', $memberId)
            ->exists();

        return $exists ? $memberId : $fallbackUserId;
    }

    private function isFinalStatus(UngTuyen $ungTuyen): bool
    {
        return in_array((int) $ungTuyen->trang_thai, UngTuyen::TRANG_THAI_CUOI, true);
    }

    private function shouldSendInterviewNotification(UngTuyen $ungTuyen, array $dataUpdate): bool
    {
        $incomingDate = $dataUpdate['ngay_hen_phong_van'] ?? null;
        $incomingMethod = $dataUpdate['hinh_thuc_phong_van'] ?? null;
        $incomingInterviewer = $dataUpdate['nguoi_phong_van'] ?? null;
        $incomingLink = $dataUpdate['link_phong_van'] ?? null;

        if (empty($incomingDate)) {
            return false;
        }

        $current = $ungTuyen->ngay_hen_phong_van?->format('Y-m-d H:i:s');
        $incoming = date('Y-m-d H:i:s', strtotime((string) $incomingDate));

        if ($current !== $incoming) {
            return true;
        }

        return (string) ($ungTuyen->hinh_thuc_phong_van ?? '') !== (string) ($incomingMethod ?? '')
            || (string) ($ungTuyen->nguoi_phong_van ?? '') !== (string) ($incomingInterviewer ?? '')
            || (string) ($ungTuyen->link_phong_van ?? '') !== (string) ($incomingLink ?? '');
    }

    private function dispatchInterviewNotification(UngTuyen $ungTuyen, bool $wasRescheduled = false): void
    {
        $ungTuyen->loadMissing([
            'tinTuyenDung.congTy',
            'hoSo.nguoiDung',
        ]);

        $ungVien = $ungTuyen->hoSo?->nguoiDung;

        if (!$ungVien || !$ungVien->email) {
            return;
        }

        dispatch(function () use ($ungVien, $ungTuyen, $wasRescheduled): void {
            $ungTuyenFresh = $ungTuyen->fresh(['tinTuyenDung.congTy', 'hoSo.nguoiDung']);
            $ungVien->notify(new InterviewScheduledNotification($ungTuyenFresh, $wasRescheduled));
        })->afterResponse();
    }

    private function shouldSendStatusNotification(UngTuyen $ungTuyen, int $trangThaiMoi): bool
    {
        return (int) $ungTuyen->trang_thai !== $trangThaiMoi
            && in_array($trangThaiMoi, UngTuyen::TRANG_THAI_CUOI, true);
    }

    /**
     * Xem danh sách CV ứng viên đã nộp vào CÔNG TY CỦA ĐANG ĐĂNG NHẬP
     */
    public function index(Request $request): JsonResponse
    {
        $congTy = $this->getCurrentEmployerCompany();

        if (!$congTy) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng thiết lập thông tin công ty trước.'
            ], 403);
        }

        // Lấy danh sách hồ sơ nộp vào các tin tuyển dụng của công ty
        $query = UngTuyen::whereHas('tinTuyenDung', function ($q) use ($congTy) {
            $q->where('cong_ty_id', $congTy->id);
        })->with([
            'tinTuyenDung' => function ($q) {
                $q->select('id', 'tieu_de', 'hinh_thuc_lam_viec', 'trang_thai', 'so_luong_tuyen')
                    ->withCount([
                        'acceptedApplications as so_luong_da_nhan',
                    ])
                    ->with('hrPhuTrach:id,ho_ten,email');
            },
            'hoSo' => function ($q) {
                // Bao gồm hồ sơ đã xoá mềm 
                $q->withTrashed()
                  ->select('id', 'nguoi_dung_id', 'tieu_de_ho_so', 'muc_tieu_nghe_nghiep', 'file_cv')
                  ->with('nguoiDung:id,email'); // Chỉ lấy email
            },
            'hrPhuTrach:id,ho_ten,email',
        ]);

        // Lọc theo tin tuyển dụng cụ thể (VD chọn xem danh sách của chỉ 1 tin)
        if ($request->has('tin_tuyen_dung_id') && $request->tin_tuyen_dung_id !== '') {
            $query->where('tin_tuyen_dung_id', $request->tin_tuyen_dung_id);
        }

        // Lọc theo trạng thái hồ sơ 
        if ($request->has('trang_thai') && $request->trang_thai !== '') {
            $query->where('trang_thai', $request->trang_thai);
        }

        if ($request->filled('hr_phu_trach_id')) {
            $hrPhuTrachId = $request->input('hr_phu_trach_id') === 'me'
                ? (int) auth()->id()
                : (int) $request->input('hr_phu_trach_id');

            $query->where('hr_phu_trach_id', $hrPhuTrachId);
        }

        $query->orderBy('thoi_gian_ung_tuyen', 'desc');

        $ungTuyens = $query->paginate((int) $request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $ungTuyens
        ]);
    }

    public function generateInterviewCopilot(Request $request, $id): JsonResponse
    {
        $congTy = $this->getCurrentEmployerCompany();
        $user = $this->getAuthenticatedEmployer();

        if (!$congTy) {
            return response()->json(['success' => false, 'message' => 'Vui lòng thiết lập thông tin công ty trước.'], 403);
        }

        $ungTuyen = $this->findCompanyApplication($id, $congTy);
        $this->abortIfCannotManageApplicationRecord($user, $congTy, $ungTuyen);

        if ($this->isFinalStatus($ungTuyen)) {
            return response()->json([
                'success' => false,
                'message' => 'Đơn ứng tuyển đã có kết quả cuối nên Interview Copilot chỉ được xem lại, không thể tạo mới.',
            ], 422);
        }

        $context = $this->buildInterviewCopilotContext($ungTuyen);
        $usedFallback = false;

        try {
            $response = $this->aiClientService->generateInterviewCopilot((int) $ungTuyen->id, $context);
            $copilot = $this->normalizeInterviewCopilotPayload($response['data'] ?? $response, $context);
        } catch (RuntimeException $exception) {
            $usedFallback = true;
            $copilot = $this->fallbackInterviewCopilotPayload($context, $exception->getMessage());
        }

        $snapshot = $this->currentCopilotSnapshot($ungTuyen);
        $snapshot['pre_interview'] = $copilot;
        $snapshot['generated_at'] = now()->toISOString();
        $snapshot['generated_by'] = $user?->only(['id', 'ho_ten', 'email']);
        $snapshot['used_fallback'] = $usedFallback;

        $before = $this->applicationAuditSnapshot($ungTuyen);
        $ungTuyen->forceFill(['rubric_danh_gia_phong_van' => json_encode($snapshot, JSON_UNESCAPED_UNICODE)])->save();
        $ungTuyenAfter = $ungTuyen->fresh();

        $this->auditLogService->logModelAction(
            actor: $user,
            action: 'employer_interview_copilot_generated',
            description: "Sinh Interview Copilot cho đơn ứng tuyển #{$ungTuyen->id}.",
            target: $ungTuyenAfter,
            company: $congTy,
            before: $before,
            after: $this->applicationAuditSnapshot($ungTuyenAfter),
            metadata: [
                'scope' => 'interview_copilot',
                'tin_tuyen_dung_id' => $ungTuyenAfter->tin_tuyen_dung_id,
                'used_fallback' => $usedFallback,
            ],
        );

        return response()->json([
            'success' => true,
            'message' => $usedFallback
                ? 'AI service chưa phản hồi, hệ thống đã tạo bộ Copilot dự phòng.'
                : 'Đã tạo Interview Copilot.',
            'data' => $this->mapInterviewCopilotResponse($ungTuyenAfter, $snapshot),
        ]);
    }

    public function evaluateInterviewCopilot(Request $request, $id): JsonResponse
    {
        $data = $request->validate([
            'notes' => ['nullable', 'string', 'max:8000'],
            'scores' => ['nullable', 'array'],
            'scores.*' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'decision' => ['nullable', 'string', 'max:255'],
        ]);

        $congTy = $this->getCurrentEmployerCompany();
        $user = $this->getAuthenticatedEmployer();

        if (!$congTy) {
            return response()->json(['success' => false, 'message' => 'Vui lòng thiết lập thông tin công ty trước.'], 403);
        }

        $ungTuyen = $this->findCompanyApplication($id, $congTy);
        $this->abortIfCannotManageApplicationRecord($user, $congTy, $ungTuyen);

        if ($this->isFinalStatus($ungTuyen)) {
            return response()->json([
                'success' => false,
                'message' => 'Đơn ứng tuyển đã có kết quả cuối nên Interview Copilot chỉ được xem lại, không thể đánh giá lại.',
            ], 422);
        }

        $context = $this->buildInterviewCopilotContext($ungTuyen);
        $notes = [
            'notes' => $data['notes'] ?? '',
            'scores' => $data['scores'] ?? [],
            'decision' => $data['decision'] ?? null,
        ];
        $usedFallback = false;

        try {
            $response = $this->aiClientService->evaluateInterviewCopilot((int) $ungTuyen->id, $context, $notes);
            $evaluation = $this->normalizeInterviewEvaluationPayload($response['data'] ?? $response, $notes);
        } catch (RuntimeException $exception) {
            $usedFallback = true;
            $evaluation = $this->fallbackInterviewEvaluationPayload($notes, $exception->getMessage());
        }

        $snapshot = $this->currentCopilotSnapshot($ungTuyen);
        $snapshot['post_interview'] = $evaluation;
        $snapshot['evaluated_at'] = now()->toISOString();
        $snapshot['evaluated_by'] = $user?->only(['id', 'ho_ten', 'email']);
        $snapshot['used_evaluation_fallback'] = $usedFallback;

        $before = $this->applicationAuditSnapshot($ungTuyen);
        $ungTuyen->forceFill([
            'rubric_danh_gia_phong_van' => json_encode($snapshot, JSON_UNESCAPED_UNICODE),
            'ket_qua_phong_van' => $evaluation['summary'] ?? $ungTuyen->ket_qua_phong_van,
            'ghi_chu' => $notes['notes'] ?: $ungTuyen->ghi_chu,
        ])->save();
        $ungTuyenAfter = $ungTuyen->fresh();

        $this->auditLogService->logModelAction(
            actor: $user,
            action: 'employer_interview_copilot_evaluated',
            description: "Đánh giá sau phỏng vấn bằng Interview Copilot cho đơn ứng tuyển #{$ungTuyen->id}.",
            target: $ungTuyenAfter,
            company: $congTy,
            before: $before,
            after: $this->applicationAuditSnapshot($ungTuyenAfter),
            metadata: [
                'scope' => 'interview_copilot',
                'tin_tuyen_dung_id' => $ungTuyenAfter->tin_tuyen_dung_id,
                'used_fallback' => $usedFallback,
            ],
        );

        return response()->json([
            'success' => true,
            'message' => $usedFallback
                ? 'AI service chưa phản hồi, hệ thống đã tạo đánh giá dự phòng.'
                : 'Đã tạo đánh giá sau phỏng vấn.',
            'data' => $this->mapInterviewCopilotResponse($ungTuyenAfter, $snapshot),
        ]);
    }

    private function findCompanyApplication(int|string $id, CongTy $congTy): UngTuyen
    {
        return UngTuyen::whereHas('tinTuyenDung', function ($q) use ($congTy) {
            $q->where('cong_ty_id', $congTy->id);
        })->with([
            'tinTuyenDung.congTy',
            'tinTuyenDung.nganhNghes:id,ten_nganh',
            'tinTuyenDung.kyNangYeuCaus.kyNang:id,ten_ky_nang',
            'hoSo' => fn ($q) => $q->withTrashed()->with(['nguoiDung.kyNangs:id,ten_ky_nang', 'parsing']),
            'hrPhuTrach:id,ho_ten,email',
        ])->findOrFail($id);
    }

    private function buildInterviewCopilotContext(UngTuyen $ungTuyen): array
    {
        $tin = $ungTuyen->tinTuyenDung;
        $hoSo = $ungTuyen->hoSo;
        $candidate = $hoSo?->nguoiDung;

        return [
            'application' => [
                'id' => $ungTuyen->id,
                'status' => $ungTuyen->trang_thai,
                'interview_round' => $ungTuyen->vong_phong_van_hien_tai ?: 'hr',
                'interview_time' => optional($ungTuyen->ngay_hen_phong_van)?->toISOString(),
                'interview_mode' => $ungTuyen->hinh_thuc_phong_van,
                'interviewer' => $ungTuyen->nguoi_phong_van,
                'current_notes' => $ungTuyen->ghi_chu,
                'current_result' => $ungTuyen->ket_qua_phong_van,
            ],
            'job' => [
                'id' => $tin?->id,
                'title' => $tin?->tieu_de,
                'description' => $tin?->mo_ta_cong_viec,
                'requirements' => $tin?->yeu_cau_cong_viec,
                'experience' => $tin?->kinh_nghiem_yeu_cau,
                'education' => $tin?->trinh_do_yeu_cau,
                'level' => $tin?->cap_bac,
                'industries' => $tin?->nganhNghes?->pluck('ten_nganh')->values()->all() ?? [],
                'skills' => $tin?->kyNangYeuCaus?->map(fn ($item) => $item->kyNang?->ten_ky_nang)->filter()->values()->all() ?? [],
            ],
            'candidate' => [
                'id' => $candidate?->id,
                'name' => $candidate?->ho_ten,
                'email' => $candidate?->email,
                'profile_title' => $hoSo?->tieu_de_ho_so,
                'summary' => $hoSo?->mo_ta_ban_than,
                'career_goal' => $hoSo?->muc_tieu_nghe_nghiep,
                'education' => $hoSo?->trinh_do,
                'years_experience' => $hoSo?->kinh_nghiem_nam,
                'target_position' => $hoSo?->vi_tri_ung_tuyen_muc_tieu,
                'target_industry' => $hoSo?->ten_nganh_nghe_muc_tieu,
                'skills' => array_values(array_unique([
                    ...($candidate?->kyNangs?->pluck('ten_ky_nang')->all() ?? []),
                    ...$this->extractNames($hoSo?->ky_nang_json ?? []),
                    ...$this->extractNames($hoSo?->parsing?->parsed_skills_json ?? []),
                ])),
                'experiences' => $hoSo?->kinh_nghiem_json ?: [],
                'projects' => $hoSo?->du_an_json ?: [],
                'certificates' => $hoSo?->chung_chi_json ?: [],
                'parsed_text' => mb_substr((string) ($hoSo?->parsing?->raw_text ?? ''), 0, 6000),
            ],
        ];
    }

    private function normalizeInterviewCopilotPayload(array $payload, array $context): array
    {
        return [
            'candidate_summary' => (string) ($payload['candidate_summary'] ?? $payload['tom_tat_ung_vien'] ?? $this->fallbackCandidateSummary($context)),
            'focus_areas' => $this->normalizeList($payload['focus_areas'] ?? $payload['trong_tam_phong_van'] ?? [], [
                'Làm rõ kinh nghiệm thực tế liên quan trực tiếp tới JD.',
                'Kiểm tra các kỹ năng còn thiếu hoặc chưa thể hiện rõ trong CV.',
            ]),
            'questions' => $this->normalizeQuestionGroups($payload['questions'] ?? $payload['cau_hoi'] ?? []),
            'rubric' => $this->normalizeRubric($payload['rubric'] ?? $payload['tieu_chi_danh_gia'] ?? []),
            'red_flags' => $this->normalizeList($payload['red_flags'] ?? $payload['rui_ro'] ?? [], [
                'Thông tin CV chưa đủ để xác nhận mức độ đóng góp thực tế.',
            ]),
            'model_version' => $payload['model_version'] ?? 'interview_copilot_v1',
        ];
    }

    private function normalizeInterviewEvaluationPayload(array $payload, array $notes): array
    {
        return [
            'summary' => (string) ($payload['summary'] ?? $payload['tom_tat'] ?? $this->fallbackEvaluationSummary($notes)),
            'strengths' => $this->normalizeList($payload['strengths'] ?? $payload['diem_manh'] ?? [], ['Có tín hiệu phù hợp, cần HR xác nhận lại theo rubric.']),
            'concerns' => $this->normalizeList($payload['concerns'] ?? $payload['rui_ro'] ?? $payload['diem_can_luu_y'] ?? [], ['Chưa đủ dữ liệu để kết luận tuyệt đối.']),
            'next_steps' => $this->normalizeList($payload['next_steps'] ?? $payload['buoc_tiep_theo'] ?? [], ['Đối chiếu ghi chú phỏng vấn với yêu cầu JD trước khi đổi trạng thái.']),
            'recommendation' => (string) ($payload['recommendation'] ?? $payload['khuyen_nghi'] ?? ($notes['decision'] ?: 'HR cần ra quyết định cuối cùng dựa trên kết quả phỏng vấn.')),
            'model_version' => $payload['model_version'] ?? 'interview_copilot_v1',
            'input_notes' => $notes,
        ];
    }

    private function fallbackInterviewCopilotPayload(array $context, ?string $aiError = null): array
    {
        $jobSkills = $context['job']['skills'] ?? [];
        $candidateSkills = $context['candidate']['skills'] ?? [];
        $missing = array_values(array_diff($jobSkills, $candidateSkills));

        return [
            'candidate_summary' => $this->fallbackCandidateSummary($context),
            'focus_areas' => [
                'Xác nhận kinh nghiệm liên quan tới vị trí ' . ($context['job']['title'] ?? 'đang tuyển') . '.',
                $missing ? 'Làm rõ các kỹ năng chưa thể hiện rõ: ' . implode(', ', array_slice($missing, 0, 4)) . '.' : 'Kiểm tra chiều sâu các kỹ năng đã khớp với JD.',
                'Đánh giá khả năng giao tiếp, phối hợp và mức độ phù hợp văn hóa.',
            ],
            'questions' => [
                [
                    'group' => 'Kinh nghiệm chuyên môn',
                    'items' => [
                        'Bạn hãy mô tả dự án/công việc gần nhất có liên quan trực tiếp tới vị trí này?',
                        'Trong dự án đó, phần nào do bạn trực tiếp chịu trách nhiệm và kết quả đo lường ra sao?',
                    ],
                ],
                [
                    'group' => 'Kỹ năng theo JD',
                    'items' => $missing
                        ? array_map(fn ($skill) => "Bạn đã từng sử dụng {$skill} trong tình huống thực tế nào?", array_slice($missing, 0, 4))
                        : ['Bạn tự đánh giá kỹ năng nào phù hợp nhất với JD này và vì sao?'],
                ],
                [
                    'group' => 'Hành vi và phối hợp',
                    'items' => [
                        'Khi gặp yêu cầu mơ hồ hoặc thay đổi liên tục, bạn xử lý như thế nào?',
                        'Hãy kể một tình huống bạn phải phối hợp với người khác để giải quyết vấn đề khó.',
                    ],
                ],
            ],
            'rubric' => [
                ['criterion' => 'Phù hợp kỹ năng', 'weight' => 35, 'expectation' => 'Có ví dụ thực tế, nêu rõ công cụ/kỹ thuật và mức độ thành thạo.'],
                ['criterion' => 'Kinh nghiệm liên quan', 'weight' => 25, 'expectation' => 'Kinh nghiệm gắn với trách nhiệm trong JD và có kết quả cụ thể.'],
                ['criterion' => 'Tư duy giải quyết vấn đề', 'weight' => 20, 'expectation' => 'Có cách phân tích, ưu tiên và xử lý trade-off rõ ràng.'],
                ['criterion' => 'Giao tiếp/phù hợp văn hóa', 'weight' => 20, 'expectation' => 'Trả lời mạch lạc, hợp tác tốt, phù hợp cách làm việc của công ty.'],
            ],
            'red_flags' => [
                'Không nêu được vai trò cá nhân trong dự án.',
                'Không có minh chứng cụ thể cho kỹ năng chính.',
                'Kỳ vọng hoặc định hướng không khớp vị trí.',
            ],
            'model_version' => 'local_fallback_interview_copilot_v1',
            'ai_error' => $aiError,
        ];
    }

    private function fallbackInterviewEvaluationPayload(array $notes, ?string $aiError = null): array
    {
        $scores = array_filter($notes['scores'] ?? [], fn ($score) => is_numeric($score));
        $average = $scores ? round(array_sum($scores) / count($scores), 1) : null;
        $summary = $average !== null
            ? "Điểm rubric trung bình {$average}/10. Cần HR đối chiếu với ghi chú phỏng vấn trước khi ra quyết định."
            : 'Đã ghi nhận nhận xét phỏng vấn. Cần HR đọc lại ghi chú và rubric để ra quyết định.';

        return [
            'summary' => $summary,
            'strengths' => ['Có dữ liệu phỏng vấn để đánh giá sâu hơn CV.'],
            'concerns' => ['Đánh giá được tạo bằng fallback vì AI service chưa phản hồi.'],
            'next_steps' => ['Xác nhận quyết định với HR phụ trách và cập nhật trạng thái ứng tuyển.'],
            'recommendation' => $notes['decision'] ?: ($average !== null && $average >= 7 ? 'Có thể cân nhắc cho bước tiếp theo.' : 'Cần xem xét thêm trước khi đi tiếp.'),
            'model_version' => 'local_fallback_interview_copilot_v1',
            'input_notes' => $notes,
            'ai_error' => $aiError,
        ];
    }

    private function currentCopilotSnapshot(UngTuyen $ungTuyen): array
    {
        $decoded = json_decode((string) $ungTuyen->rubric_danh_gia_phong_van, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function mapInterviewCopilotResponse(UngTuyen $ungTuyen, array $snapshot): array
    {
        return [
            'ung_tuyen_id' => $ungTuyen->id,
            'copilot' => $snapshot,
            'rubric_danh_gia_phong_van' => $ungTuyen->rubric_danh_gia_phong_van,
            'ket_qua_phong_van' => $ungTuyen->ket_qua_phong_van,
            'ghi_chu' => $ungTuyen->ghi_chu,
        ];
    }

    private function normalizeQuestionGroups(mixed $questions): array
    {
        if (!is_array($questions) || !$questions) {
            return [
                ['group' => 'Kinh nghiệm chuyên môn', 'items' => ['Bạn hãy mô tả kinh nghiệm liên quan nhất tới vị trí này?']],
                ['group' => 'Hành vi', 'items' => ['Bạn xử lý thế nào khi gặp vấn đề khó trong công việc?']],
            ];
        }

        return collect($questions)->map(function ($group, $index) {
            if (is_string($group)) {
                return ['group' => 'Nhóm câu hỏi ' . ($index + 1), 'items' => [$group]];
            }

            if (is_array($group)) {
                return [
                    'group' => (string) ($group['group'] ?? $group['category'] ?? $group['nhom'] ?? ('Nhóm câu hỏi ' . ($index + 1))),
                    'items' => $this->normalizeList($group['items'] ?? $group['questions'] ?? $group['cau_hoi'] ?? []),
                ];
            }

            return null;
        })->filter()->values()->all();
    }

    private function normalizeRubric(mixed $rubric): array
    {
        if (!is_array($rubric) || !$rubric) {
            return [
                ['criterion' => 'Phù hợp kỹ năng', 'weight' => 35, 'expectation' => 'Kỹ năng đáp ứng yêu cầu chính của JD.'],
                ['criterion' => 'Kinh nghiệm liên quan', 'weight' => 25, 'expectation' => 'Có kinh nghiệm tương tự vị trí tuyển dụng.'],
                ['criterion' => 'Tư duy giải quyết vấn đề', 'weight' => 20, 'expectation' => 'Có lập luận rõ ràng khi xử lý tình huống.'],
                ['criterion' => 'Giao tiếp/phù hợp văn hóa', 'weight' => 20, 'expectation' => 'Giao tiếp rõ, hợp tác tốt.'],
            ];
        }

        return collect($rubric)->map(function ($item) {
            if (is_string($item)) {
                return ['criterion' => $item, 'weight' => null, 'expectation' => null];
            }

            if (is_array($item)) {
                return [
                    'criterion' => (string) ($item['criterion'] ?? $item['tieu_chi'] ?? $item['name'] ?? 'Tiêu chí'),
                    'weight' => isset($item['weight']) || isset($item['trong_so']) ? (int) ($item['weight'] ?? $item['trong_so']) : null,
                    'expectation' => $item['expectation'] ?? $item['ky_vong'] ?? $item['description'] ?? null,
                ];
            }

            return null;
        })->filter()->values()->all();
    }

    private function normalizeList(mixed $items, array $fallback = []): array
    {
        if (is_string($items) && trim($items) !== '') {
            return [trim($items)];
        }

        if (!is_array($items)) {
            return $fallback;
        }

        $normalized = collect($items)
            ->map(fn ($item) => is_array($item) ? ($item['text'] ?? $item['value'] ?? json_encode($item, JSON_UNESCAPED_UNICODE)) : $item)
            ->filter()
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->values()
            ->all();

        return $normalized ?: $fallback;
    }

    private function fallbackCandidateSummary(array $context): string
    {
        $candidate = $context['candidate'] ?? [];
        $job = $context['job'] ?? [];

        return trim(($candidate['name'] ?? 'Ứng viên')
            . ' ứng tuyển vị trí '
            . ($job['title'] ?? 'đang tuyển')
            . '. Hồ sơ: '
            . ($candidate['profile_title'] ?? 'chưa có tiêu đề rõ ràng')
            . '. Kinh nghiệm: '
            . (($candidate['years_experience'] ?? null) !== null ? $candidate['years_experience'] . ' năm' : 'chưa cập nhật')
            . '.');
    }

    private function fallbackEvaluationSummary(array $notes): string
    {
        $plainNotes = trim((string) ($notes['notes'] ?? ''));

        return $plainNotes
            ? 'Tóm tắt ghi chú phỏng vấn: ' . mb_substr($plainNotes, 0, 220)
            : 'Chưa có ghi chú phỏng vấn đủ chi tiết để tổng hợp.';
    }

    private function extractNames(array $items): array
    {
        return collect($items)
            ->map(function ($item) {
                if (is_string($item)) {
                    return $item;
                }

                if (is_array($item)) {
                    return $item['skill_name']
                        ?? $item['ten_ky_nang']
                        ?? $item['ten']
                        ?? $item['ky_nang']
                        ?? $item['cong_nghe']
                        ?? $item['name']
                        ?? $item['title']
                        ?? $item['value']
                        ?? null;
                }

                return null;
            })
            ->filter()
            ->values()
            ->all();
    }

    public function notificationTemplates(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                UngTuyen::TRANG_THAI_CHO_DUYET => [
                    'status_label' => 'Chờ duyệt',
                    'title' => 'Đã nhận hồ sơ ứng tuyển',
                    'subject' => 'Cảm ơn bạn đã ứng tuyển',
                    'body' => 'Chúng tôi đã nhận được hồ sơ của bạn và sẽ phản hồi sau khi hoàn tất bước sàng lọc ban đầu.',
                    'usage_hint' => 'Dùng khi muốn xác nhận đã nhận hồ sơ, chưa đưa ra kết quả.',
                ],
                UngTuyen::TRANG_THAI_DA_XEM => [
                    'status_label' => 'Đã xem',
                    'title' => 'Hồ sơ đang được xem xét',
                    'subject' => 'Hồ sơ của bạn đang được nhà tuyển dụng xem xét',
                    'body' => 'Hồ sơ của bạn đã được chuyển sang bước xem xét chi tiết. Chúng tôi sẽ liên hệ nếu hồ sơ phù hợp với yêu cầu tuyển dụng.',
                    'usage_hint' => 'Dùng khi HR đã đọc CV nhưng chưa quyết định phỏng vấn.',
                ],
                UngTuyen::TRANG_THAI_DA_HEN_PHONG_VAN => [
                    'status_label' => 'Đã hẹn phỏng vấn',
                    'title' => 'Thư mời phỏng vấn',
                    'subject' => 'Lịch phỏng vấn cho vị trí ứng tuyển',
                    'body' => 'Bạn được mời tham gia phỏng vấn. Vui lòng kiểm tra thời gian, hình thức phỏng vấn và phản hồi xác nhận tham gia.',
                    'usage_hint' => 'Dùng khi có lịch phỏng vấn cụ thể. Hệ thống sẽ gửi email lịch hẹn nếu có ngày hẹn.',
                ],
                UngTuyen::TRANG_THAI_QUA_PHONG_VAN => [
                    'status_label' => 'Qua phỏng vấn',
                    'title' => 'Cập nhật kết quả phỏng vấn',
                    'subject' => 'Bạn đã vượt qua vòng phỏng vấn',
                    'body' => 'Chúc mừng bạn đã vượt qua vòng phỏng vấn hiện tại. Nhà tuyển dụng sẽ tiếp tục liên hệ về bước tiếp theo trong quy trình tuyển dụng.',
                    'usage_hint' => 'Dùng khi ứng viên qua vòng nhưng chưa phải kết quả cuối.',
                ],
                UngTuyen::TRANG_THAI_TRUNG_TUYEN => [
                    'status_label' => 'Trúng tuyển',
                    'title' => 'Thông báo kết quả trúng tuyển',
                    'subject' => 'Chúc mừng bạn đã trúng tuyển',
                    'body' => 'Chúc mừng bạn đã được chọn cho vị trí ứng tuyển. Nhà tuyển dụng sẽ liên hệ để trao đổi các bước nhận việc tiếp theo.',
                    'usage_hint' => 'Dùng cho kết quả cuối tích cực. Hệ thống sẽ gửi email kết quả.',
                ],
                UngTuyen::TRANG_THAI_TU_CHOI => [
                    'status_label' => 'Từ chối',
                    'title' => 'Thông báo kết quả ứng tuyển',
                    'subject' => 'Cập nhật kết quả ứng tuyển',
                    'body' => 'Cảm ơn bạn đã quan tâm và dành thời gian ứng tuyển. Sau quá trình xem xét, hồ sơ của bạn chưa phù hợp với vị trí hiện tại. Chúc bạn thành công trong các cơ hội tiếp theo.',
                    'usage_hint' => 'Dùng cho kết quả cuối chưa phù hợp. Nên giữ văn phong lịch sự, không nêu đánh giá nhạy cảm.',
                ],
            ],
        ]);
    }

    /**
     * Đổi trạng thái xử lý đơn ứng tuyển
     */
    public function updateTrangThai(CapNhatTrangThaiRequest $request, $id): JsonResponse
    {
        $congTy = $this->getCurrentEmployerCompany();
        $user = $this->getAuthenticatedEmployer();

        if (!$congTy) {
            return response()->json(['success' => false, 'message' => 'Lỗi công ty'], 403);
        }

        $ungTuyen = UngTuyen::whereHas('tinTuyenDung', function ($q) use ($congTy) {
            $q->where('cong_ty_id', $congTy->id);
        })->with('tinTuyenDung:id,cong_ty_id,hr_phu_trach_id')->findOrFail($id);
        $this->abortIfCannotManageApplicationRecord($user, $congTy, $ungTuyen);

        $trangThaiMoi = (int) $request->trang_thai;

        if ($ungTuyen->da_rut_don) {
            return response()->json([
                'success' => false,
                'message' => 'Ứng viên đã rút đơn ứng tuyển nên không thể cập nhật xử lý nữa.',
            ], 422);
        }

        if ($this->isFinalStatus($ungTuyen) && (int) $ungTuyen->trang_thai !== $trangThaiMoi) {
            return response()->json([
                'success' => false,
                'message' => 'Đơn ứng tuyển đã có kết quả cuối nên không thể đổi sang trạng thái khác.',
            ], 422);
        }

        if (
            $trangThaiMoi === UngTuyen::TRANG_THAI_TRUNG_TUYEN
            && (int) $ungTuyen->trang_thai !== UngTuyen::TRANG_THAI_TRUNG_TUYEN
        ) {
            $tin = $ungTuyen->tinTuyenDung()
                ->withCount([
                    'acceptedApplications as so_luong_da_nhan',
                ])
                ->first();

            if ($tin && $tin->so_luong_con_lai <= 0) {
                return response()->json([
                    'success' => false,
                        'message' => 'Tin tuyển dụng đã đủ chỉ tiêu. Không thể đánh dấu trúng tuyển thêm ứng viên.',
                    'data' => [
                        'so_luong_tuyen' => $tin->so_luong_tuyen,
                        'so_luong_da_nhan' => $tin->so_luong_da_nhan,
                        'so_luong_con_lai' => $tin->so_luong_con_lai,
                    ],
                ], 422);
            }
        }

        if (
            $request->filled('ngay_hen_phong_van')
            && !in_array($trangThaiMoi, UngTuyen::TRANG_THAI_CUOI, true)
            && $trangThaiMoi < UngTuyen::TRANG_THAI_DA_HEN_PHONG_VAN
        ) {
            $trangThaiMoi = UngTuyen::TRANG_THAI_DA_HEN_PHONG_VAN;
        }

        $dataUpdate = [
            'trang_thai' => $trangThaiMoi
        ];

        if ($request->has('ngay_hen_phong_van')) {
            $dataUpdate['ngay_hen_phong_van'] = $request->ngay_hen_phong_van;
        }

        if ($request->has('hinh_thuc_phong_van')) {
            $dataUpdate['hinh_thuc_phong_van'] = $request->hinh_thuc_phong_van;
        }

        if ($request->has('nguoi_phong_van')) {
            $dataUpdate['nguoi_phong_van'] = $request->nguoi_phong_van;
        }

        if ($request->has('link_phong_van')) {
            $dataUpdate['link_phong_van'] = $request->link_phong_van;
        }

        if ($request->has('ket_qua_phong_van')) {
            $dataUpdate['ket_qua_phong_van'] = $request->ket_qua_phong_van;
        }

        if ($request->has('ghi_chu')) {
            $dataUpdate['ghi_chu'] = $request->ghi_chu;
        }

        if ($request->has('hr_phu_trach_id')) {
            if (!$this->coTheQuanLyTatCaBanGhiEmployer($user, $congTy)) {
                $dataUpdate['hr_phu_trach_id'] = (int) auth()->id();
            } else {
                $dataUpdate['hr_phu_trach_id'] = $this->resolveValidHrPhuTrachId(
                    $request->hr_phu_trach_id ? (int) $request->hr_phu_trach_id : null,
                    $congTy,
                    (int) auth()->id(),
                );
            }
        } elseif (empty($ungTuyen->hr_phu_trach_id)) {
            $dataUpdate['hr_phu_trach_id'] = (int) auth()->id();
        }

        $before = $this->applicationAuditSnapshot($ungTuyen);
        $shouldNotifyInterview = $this->shouldSendInterviewNotification($ungTuyen, $dataUpdate);
        $shouldNotifyStatus = !$shouldNotifyInterview && $this->shouldSendStatusNotification($ungTuyen, $trangThaiMoi);
        $wasRescheduled = !is_null($ungTuyen->ngay_hen_phong_van);

        if ($request->has('ngay_hen_phong_van')) {
            if (empty($dataUpdate['ngay_hen_phong_van'])) {
                $dataUpdate['trang_thai_tham_gia_phong_van'] = null;
                $dataUpdate['thoi_gian_phan_hoi_phong_van'] = null;
            } elseif ($shouldNotifyInterview) {
                $dataUpdate['trang_thai_tham_gia_phong_van'] = UngTuyen::PHONG_VAN_CHO_XAC_NHAN;
                $dataUpdate['thoi_gian_phan_hoi_phong_van'] = null;
                $dataUpdate['thoi_gian_gui_nhac_lich'] = null;
            }
        }

        $ungTuyen->update($dataUpdate);
        $ungTuyenAfter = $ungTuyen->fresh(['tinTuyenDung:id,cong_ty_id,tieu_de']);

        $auditAction = match (true) {
            $shouldNotifyInterview && $wasRescheduled => 'employer_interview_rescheduled',
            $shouldNotifyInterview => 'employer_interview_scheduled',
            default => 'employer_application_updated',
        };

        $this->auditLogService->logModelAction(
            actor: $user,
            action: $auditAction,
            description: "Cập nhật đơn ứng tuyển #{$ungTuyen->id}.",
            target: $ungTuyenAfter,
            company: $congTy,
            before: $before,
            after: $this->applicationAuditSnapshot($ungTuyenAfter),
            metadata: [
                'scope' => 'employer_application',
                'tin_tuyen_dung_id' => $ungTuyenAfter->tin_tuyen_dung_id,
                'tin_tuyen_dung_tieu_de' => $ungTuyenAfter->tinTuyenDung?->tieu_de,
            ],
        );
        $this->broadcastApplicationChanged($ungTuyenAfter, $auditAction, [
            'should_notify_interview' => $shouldNotifyInterview,
            'should_notify_status' => $shouldNotifyStatus,
        ]);

        $ungTuyenAfter->loadMissing(['hoSo.nguoiDung', 'tinTuyenDung.congTy']);
        $candidate = $ungTuyenAfter->hoSo?->nguoiDung;
        $jobTitle = $ungTuyenAfter->tinTuyenDung?->tieu_de ?: 'vị trí ứng tuyển';
        $companyName = $ungTuyenAfter->tinTuyenDung?->congTy?->ten_cong_ty ?: 'nhà tuyển dụng';

        if ($candidate) {
            if ($shouldNotifyInterview) {
                $this->appNotificationService->createForUser(
                    $candidate,
                    $wasRescheduled ? 'candidate_interview_rescheduled' : 'candidate_interview_scheduled',
                    $wasRescheduled ? 'Lịch phỏng vấn đã được cập nhật' : 'Bạn có lịch phỏng vấn mới',
                    "{$companyName} đã " . ($wasRescheduled ? 'cập nhật lịch phỏng vấn' : 'đặt lịch phỏng vấn') . " cho vị trí {$jobTitle}.",
                    '/applications',
                    ['ung_tuyen_id' => $ungTuyenAfter->id, 'tin_tuyen_dung_id' => $ungTuyenAfter->tin_tuyen_dung_id],
                );
            } elseif ($shouldNotifyStatus) {
                $this->appNotificationService->createForUser(
                    $candidate,
                    'candidate_application_status_updated',
                    'Đơn ứng tuyển đã có cập nhật',
                    "{$companyName} đã cập nhật trạng thái đơn ứng tuyển cho vị trí {$jobTitle}.",
                    '/applications',
                    ['ung_tuyen_id' => $ungTuyenAfter->id, 'trang_thai' => $ungTuyenAfter->trang_thai],
                );
            }
        }

        if ($shouldNotifyInterview || $shouldNotifyStatus) {
            if ($shouldNotifyInterview) {
                $this->dispatchInterviewNotification($ungTuyen, $wasRescheduled);
            } else {
                $ungTuyen->loadMissing([
                    'tinTuyenDung.congTy',
                    'hoSo.nguoiDung',
                ]);

                $ungVien = $ungTuyen->hoSo?->nguoiDung;

                if ($ungVien && $ungVien->email) {
                    dispatch(function () use ($ungVien, $ungTuyen): void {
                        $ungTuyenFresh = $ungTuyen->fresh(['tinTuyenDung.congTy', 'hoSo.nguoiDung']);
                        $ungVien->notify(new ApplicationStatusNotification($ungTuyenFresh));
                    })->afterResponse();
                }
            }
        }

        $message = 'Cập nhật trạng thái vòng phỏng vấn/ứng tuyển thành công.';

        if ($shouldNotifyInterview) {
            $message = 'Cập nhật trạng thái thành công và đã gửi email lịch phỏng vấn cho ứng viên.';
        } elseif ($shouldNotifyStatus) {
            $message = 'Cập nhật trạng thái thành công và đã gửi email thông báo kết quả cho ứng viên.';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $ungTuyen->fresh()->load([
                'tinTuyenDung' => function ($q) {
                    $q->select('id', 'tieu_de', 'hinh_thuc_lam_viec', 'trang_thai', 'so_luong_tuyen')
                        ->withCount([
                            'acceptedApplications as so_luong_da_nhan',
                        ])
                        ->with('hrPhuTrach:id,ho_ten,email');
                },
                'hrPhuTrach:id,ho_ten,email',
            ])
        ]);
    }

    public function guiLaiEmailPhongVan(Request $request, $id): JsonResponse
    {
        $congTy = $this->getCurrentEmployerCompany();
        $user = $this->getAuthenticatedEmployer();

        if (!$congTy) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng thiết lập thông tin công ty trước.'
            ], 403);
        }

        $ungTuyen = UngTuyen::whereHas('tinTuyenDung', function ($q) use ($congTy) {
            $q->where('cong_ty_id', $congTy->id);
        })->with(['tinTuyenDung.congTy', 'hoSo.nguoiDung'])->findOrFail($id);
        $this->abortIfCannotManageApplicationRecord($user, $congTy, $ungTuyen);

        if ($ungTuyen->da_rut_don) {
            return response()->json([
                'success' => false,
                'message' => 'Ứng viên đã rút đơn nên không thể gửi lại email lịch phỏng vấn.',
            ], 422);
        }

        if ($this->isFinalStatus($ungTuyen)) {
            return response()->json([
                'success' => false,
                'message' => 'Đơn ứng tuyển đã có kết quả cuối nên không thể gửi lại email lịch phỏng vấn.',
            ], 422);
        }

        if (!$ungTuyen->ngay_hen_phong_van) {
            return response()->json([
                'success' => false,
                'message' => 'Ứng tuyển này chưa có lịch phỏng vấn để gửi lại email.',
            ], 422);
        }

        $this->dispatchInterviewNotification($ungTuyen, true);
        $candidate = $ungTuyen->hoSo?->nguoiDung;

        if ($candidate) {
            $this->appNotificationService->createForUser(
                $candidate,
                'candidate_interview_email_resent',
                'Nhà tuyển dụng đã gửi lại lịch phỏng vấn',
                'Email lịch phỏng vấn vừa được gửi lại. Vui lòng kiểm tra hộp thư và mục ứng tuyển.',
                '/applications',
                ['ung_tuyen_id' => $ungTuyen->id],
            );
        }
        $this->auditLogService->logModelAction(
            actor: $user,
            action: 'employer_interview_email_resent',
            description: "Gửi lại email lịch phỏng vấn cho đơn ứng tuyển #{$ungTuyen->id}.",
            target: $ungTuyen,
            company: $congTy,
            after: $this->applicationAuditSnapshot($ungTuyen),
            metadata: [
                'scope' => 'employer_application',
                'tin_tuyen_dung_id' => $ungTuyen->tin_tuyen_dung_id,
            ],
        );
        $this->broadcastApplicationChanged($ungTuyen, 'interview_email_resent');

        return response()->json([
            'success' => true,
            'message' => 'Đã gửi lại email lịch phỏng vấn cho ứng viên.',
        ]);
    }
}
