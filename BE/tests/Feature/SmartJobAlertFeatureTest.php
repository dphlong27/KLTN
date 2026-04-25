<?php

use App\Models\HoSo;
use App\Models\NguoiDung;
use App\Models\SmartJobAlert;

it('lists, marks read and dismisses smart job alerts for candidate', function () {
    $candidate = NguoiDung::factory()->ungVien()->create();
    $employer = NguoiDung::factory()->nhaTuyenDung()->create();
    $company = createCompanyForEmployer($employer);
    $job = createJobForCompany($company, ['tieu_de' => 'Backend Developer Laravel']);
    $profile = HoSo::factory()->forNguoiDung($candidate->id)->create([
        'tieu_de_ho_so' => 'CV Backend',
    ]);

    $alert = SmartJobAlert::create([
        'nguoi_dung_id' => $candidate->id,
        'tin_tuyen_dung_id' => $job->id,
        'cong_ty_id' => $company->id,
        'ho_so_id' => $profile->id,
        'match_score' => 86.5,
        'match_level' => 'excellent',
        'matched_skills_json' => ['Laravel', 'REST API'],
        'missing_skills_json' => ['Docker'],
        'matched_industries_json' => ['Công nghệ thông tin'],
        'reasons_json' => ['Khớp kỹ năng Laravel'],
        'trang_thai' => SmartJobAlert::TRANG_THAI_MOI,
        'notified_at' => now(),
    ]);

    $this->actingAs($candidate, 'sanctum')
        ->getJson('/api/v1/ung-vien/smart-job-alerts')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.data.0.id', $alert->id)
        ->assertJsonPath('data.data.0.match_score', 86.5);

    $this->actingAs($candidate, 'sanctum')
        ->getJson('/api/v1/ung-vien/smart-job-alerts/thong-ke')
        ->assertOk()
        ->assertJsonPath('data.total', 1)
        ->assertJsonPath('data.new', 1)
        ->assertJsonPath('data.strong', 1);

    $this->actingAs($candidate, 'sanctum')
        ->patchJson("/api/v1/ung-vien/smart-job-alerts/{$alert->id}/read")
        ->assertOk()
        ->assertJsonPath('data.trang_thai', SmartJobAlert::TRANG_THAI_DA_DOC);

    $this->actingAs($candidate, 'sanctum')
        ->patchJson("/api/v1/ung-vien/smart-job-alerts/{$alert->id}/dismiss")
        ->assertOk();

    expect($alert->fresh()->trang_thai)->toBe(SmartJobAlert::TRANG_THAI_BO_QUA);
});

it('does not expose another candidates smart job alerts', function () {
    $candidate = NguoiDung::factory()->ungVien()->create();
    $otherCandidate = NguoiDung::factory()->ungVien()->create();
    $employer = NguoiDung::factory()->nhaTuyenDung()->create();
    $company = createCompanyForEmployer($employer);
    $job = createJobForCompany($company);

    SmartJobAlert::create([
        'nguoi_dung_id' => $otherCandidate->id,
        'tin_tuyen_dung_id' => $job->id,
        'cong_ty_id' => $company->id,
        'match_score' => 90,
        'match_level' => 'excellent',
        'trang_thai' => SmartJobAlert::TRANG_THAI_MOI,
    ]);

    $this->actingAs($candidate, 'sanctum')
        ->getJson('/api/v1/ung-vien/smart-job-alerts')
        ->assertOk()
        ->assertJsonPath('data.total', 0);
});
