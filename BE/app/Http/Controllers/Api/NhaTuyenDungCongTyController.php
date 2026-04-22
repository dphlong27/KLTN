<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesEmployerCompany;
use App\Http\Controllers\Controller;
use App\Http\Requests\CongTy\TaoCongTyRequest;
use App\Http\Requests\CongTy\CapNhatCongTyRequest;
use App\Models\AuditLog;
use App\Models\CongTy;
use App\Models\CongTyLoiMoi;
use App\Models\NguoiDung;
use App\Services\HrAuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * NhaTuyenDungCongTyController - NTD quản lý công ty của mình
 *
 * Vai trò: Nhà tuyển dụng (vai_tro = 1)
 * Mỗi NTD có thể tạo 1 công ty duy nhất.
 *
 * Routes:
 *   GET   /api/v1/nha-tuyen-dung/cong-ty        - Xem công ty của mình
 *   POST  /api/v1/nha-tuyen-dung/cong-ty        - Tạo công ty
 *   PUT   /api/v1/nha-tuyen-dung/cong-ty        - Cập nhật công ty
 */
class NhaTuyenDungCongTyController extends Controller
{
    use ResolvesEmployerCompany;

    public function __construct(
        private readonly HrAuditLogService $hrAuditLogService
    ) {
    }

    private function mapInvitationData(CongTyLoiMoi $invite): array
    {
        $invite->loadMissing([
            'nguoiMoi:id,ho_ten,email',
            'nguoiDung:id,ho_ten,email,so_dien_thoai,anh_dai_dien',
            'congTy:id,ten_cong_ty',
        ]);

        return [
            'id' => $invite->id,
            'cong_ty_id' => $invite->cong_ty_id,
            'ten_cong_ty' => $invite->congTy?->ten_cong_ty,
            'nguoi_dung_id' => $invite->nguoi_dung_id,
            'email' => $invite->email,
            'vai_tro_noi_bo' => $invite->vai_tro_noi_bo,
            'ten_vai_tro_noi_bo' => CongTy::nhanVaiTroNoiBo($invite->vai_tro_noi_bo),
            'trang_thai' => $invite->trang_thai,
            'nguoi_moi' => $invite->nguoiMoi ? [
                'id' => $invite->nguoiMoi->id,
                'ho_ten' => $invite->nguoiMoi->ho_ten,
                'email' => $invite->nguoiMoi->email,
            ] : null,
            'nguoi_duoc_moi' => $invite->nguoiDung ? [
                'id' => $invite->nguoiDung->id,
                'ho_ten' => $invite->nguoiDung->ho_ten,
                'email' => $invite->nguoiDung->email,
                'so_dien_thoai' => $invite->nguoiDung->so_dien_thoai,
                'avatar_url' => $invite->nguoiDung->anh_dai_dien
                    ? url('/api/v1/anh-dai-dien?path=' . urlencode($invite->nguoiDung->anh_dai_dien))
                    : null,
            ] : null,
            'da_co_tai_khoan' => (bool) $invite->nguoiDung,
            'created_at' => optional($invite->created_at)?->toISOString(),
            'phan_hoi_luc' => optional($invite->phan_hoi_luc)?->toISOString(),
        ];
    }

    private function mapCompanyData(CongTy $congTy): array
    {
        $user = $this->getAuthenticatedEmployer();
        $congTy->loadMissing([
            'nganhNghe:id,ten_nganh',
            'thanhViens:id,ho_ten,email,so_dien_thoai,anh_dai_dien',
            'loiMoiThanhViens.nguoiMoi:id,ho_ten,email',
            'loiMoiThanhViens.nguoiDung:id,ho_ten,email,so_dien_thoai,anh_dai_dien',
        ]);
        $congTy->loadCount('nguoiDungTheoDois');

        $data = $congTy->toArray();
        $data['logo_url'] = $congTy->logo
            ? url('/api/v1/cong-ty-logo?path=' . urlencode($congTy->logo))
            : null;
        $data['tong_so_hr'] = $congTy->thanhViens->count();
        $data['so_nguoi_theo_doi'] = $congTy->nguoi_dung_theo_dois_count ?? 0;
        $data['la_chu_so_huu'] = $this->isCompanyOwner($user, $congTy);
        $data['vai_tro_noi_bo_hien_tai'] = $user?->layVaiTroNoiBoCongTy($congTy);
        $data['ten_vai_tro_noi_bo_hien_tai'] = CongTy::nhanVaiTroNoiBo($data['vai_tro_noi_bo_hien_tai']);
        $data['quyen_noi_bo'] = CongTy::quyenTheoVaiTroNoiBo($data['vai_tro_noi_bo_hien_tai']);
        $data['vai_tro_noi_bo_options'] = CongTy::VAI_TRO_NOI_BO_LABELS;
        $data['thanh_viens'] = $congTy->thanhViens->map(function (NguoiDung $member) {
            $payload = $member->toArray();
            $payload['vai_tro_noi_bo'] = $member->pivot?->vai_tro_noi_bo;
            $payload['ten_vai_tro_noi_bo'] = CongTy::nhanVaiTroNoiBo($member->pivot?->vai_tro_noi_bo);
            $payload['la_chu_so_huu'] = $member->pivot?->vai_tro_noi_bo === CongTy::VAI_TRO_NOI_BO_OWNER;
            $payload['avatar_url'] = $member->anh_dai_dien
                ? url('/api/v1/anh-dai-dien?path=' . urlencode($member->anh_dai_dien))
                : null;

            return $payload;
        })->values()->all();
        $data['loi_moi_dang_cho'] = $congTy->loiMoiThanhViens
            ->where('trang_thai', CongTyLoiMoi::TRANG_THAI_DANG_CHO)
            ->sortByDesc('created_at')
            ->values()
            ->map(fn (CongTyLoiMoi $invite) => $this->mapInvitationData($invite))
            ->all();

        return $data;
    }

    private function mapHrAuditLogData(AuditLog $log): array
    {
        $log->loadMissing([
            'actor:id,ho_ten,email',
        ]);
        $targetUser = null;

        if ($log->target_type === NguoiDung::class && $log->target_id) {
            $targetUser = NguoiDung::select('id', 'ho_ten', 'email')->find($log->target_id);
        }

        return [
            'id' => $log->id,
            'loai_su_kien' => $log->action,
            'mo_ta' => $log->description,
            'du_lieu_bo_sung' => $log->metadata_json,
            'nguoi_thuc_hien' => $log->actor ? [
                'id' => $log->actor->id,
                'ho_ten' => $log->actor->ho_ten,
                'email' => $log->actor->email,
            ] : null,
            'nguoi_bi_tac_dong' => $targetUser ? [
                'id' => $targetUser->id,
                'ho_ten' => $targetUser->ho_ten,
                'email' => $targetUser->email,
            ] : null,
            'created_at' => optional($log->created_at)?->toISOString(),
        ];
    }

    /**
     * GET /api/v1/nha-tuyen-dung/cong-ty
     * Xem công ty của NTD đang đăng nhập.
     */
    public function show(): JsonResponse
    {
        $congTy = $this->getCurrentEmployerCompany();

        if (!$congTy) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn chưa tạo thông tin công ty.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->mapCompanyData($congTy),
        ]);
    }

    /**
     * POST /api/v1/nha-tuyen-dung/cong-ty
     * Tạo công ty (mỗi NTD chỉ 1 công ty).
     */
    public function store(TaoCongTyRequest $request): JsonResponse
    {
        $nguoiDungId = auth()->id();

        if ($this->getCurrentEmployerCompany()) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn đã thuộc một công ty rồi. Hãy cập nhật thay vì tạo mới.',
            ], 422);
        }

        $data = $request->validated();
        $data['nguoi_dung_id'] = $nguoiDungId;

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('cong_ty_logos', 'public');
        }

        $congTy = CongTy::create($data);
        $congTy->thanhViens()->syncWithoutDetaching([
            $nguoiDungId => [
                'vai_tro_noi_bo' => 'owner',
                'duoc_tao_boi' => $nguoiDungId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        $this->hrAuditLogService->log(
            $congTy,
            $this->getAuthenticatedEmployer(),
            'company_created',
            'Tạo công ty và khởi tạo owner đầu tiên.',
            $this->getAuthenticatedEmployer(),
        );

        return response()->json([
            'success' => true,
            'message' => 'Tạo công ty thành công.',
            'data' => $this->mapCompanyData($congTy),
        ], 201);
    }

    /**
     * PUT /api/v1/nha-tuyen-dung/cong-ty
     * Cập nhật công ty của mình.
     */
    public function update(CapNhatCongTyRequest $request): JsonResponse
    {
        $congTy = $this->getCurrentEmployerCompany();

        if (!$congTy) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn chưa tạo thông tin công ty.',
            ], 404);
        }

        $data = $request->validated();

        if ($request->hasFile('logo')) {
            if ($congTy->logo) {
                Storage::disk('public')->delete($congTy->logo);
            }

            $data['logo'] = $request->file('logo')->store('cong_ty_logos', 'public');
        }

        $congTy->update($data);
        $this->hrAuditLogService->log(
            $congTy,
            $this->getAuthenticatedEmployer(),
            'company_updated',
            'Cập nhật thông tin công ty.',
            null,
            ['fields' => array_keys($data)],
        );

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật công ty thành công.',
            'data' => $this->mapCompanyData($congTy->fresh()),
        ]);
    }

    public function members(): JsonResponse
    {
        $congTy = $this->getCurrentEmployerCompany();

        if (!$congTy) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn chưa thuộc công ty nào.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'cong_ty_id' => $congTy->id,
                'la_chu_so_huu' => $this->isCompanyOwner($this->getAuthenticatedEmployer(), $congTy),
                'vai_tro_noi_bo_options' => CongTy::VAI_TRO_NOI_BO_LABELS,
                'thanh_viens' => $this->mapCompanyData($congTy)['thanh_viens'],
            ],
        ]);
    }

    public function addMember(Request $request): JsonResponse
    {
        $congTy = $this->getCurrentEmployerCompany();
        $user = $this->getAuthenticatedEmployer();

        if (!$congTy || !$user) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn chưa thuộc công ty nào.',
            ], 404);
        }

        if (!$this->isCompanyOwner($user, $congTy)) {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ chủ sở hữu công ty mới có thể quản lý thành viên HR.',
            ], 403);
        }

        $data = $request->validate([
            'email' => ['required', 'email', 'max:150'],
            'vai_tro_noi_bo' => ['nullable', 'string', 'in:' . implode(',', array_filter(CongTy::danhSachVaiTroNoiBo(), fn ($role) => $role !== CongTy::VAI_TRO_NOI_BO_OWNER))],
        ]);

        $member = NguoiDung::where('email', $data['email'])->first();

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy tài khoản HR với email này. Vui lòng để HR đăng ký trước.',
            ], 404);
        }

        if (!$member->isNhaTuyenDung()) {
            return response()->json([
                'success' => false,
                'message' => 'Tài khoản này không phải nhà tuyển dụng.',
            ], 422);
        }

        if ((int) $member->id === (int) $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn đã là thành viên của công ty này.',
            ], 422);
        }

        $existingCompany = $member->congTyHienTai();
        if ($existingCompany && (int) $existingCompany->id !== (int) $congTy->id) {
            return response()->json([
                'success' => false,
                'message' => 'Tài khoản HR này đã thuộc một công ty khác.',
            ], 422);
        }

        if ($congTy->thanhViens()->where('nguoi_dungs.id', $member->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'HR này đã thuộc công ty rồi.',
            ], 422);
        }

        $congTy->loiMoiThanhViens()
            ->where('email', $member->email)
            ->where('trang_thai', CongTyLoiMoi::TRANG_THAI_DANG_CHO)
            ->update([
                'trang_thai' => CongTyLoiMoi::TRANG_THAI_DA_HUY,
                'phan_hoi_luc' => now(),
                'updated_at' => now(),
            ]);

        $congTy->thanhViens()->attach($member->id, [
            'vai_tro_noi_bo' => $data['vai_tro_noi_bo'] ?? CongTy::VAI_TRO_NOI_BO_RECRUITER,
            'duoc_tao_boi' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $assignedRole = $data['vai_tro_noi_bo'] ?? CongTy::VAI_TRO_NOI_BO_RECRUITER;
        $this->hrAuditLogService->log(
            $congTy,
            $user,
            'member_added',
            "Thêm trực tiếp {$member->email} vào công ty với vai trò " . CongTy::nhanVaiTroNoiBo($assignedRole) . '.',
            $member,
            ['vai_tro_noi_bo' => $assignedRole],
        );

        return response()->json([
            'success' => true,
            'message' => 'Đã thêm HR vào công ty.',
            'data' => [
                'cong_ty' => $this->mapCompanyData($congTy->fresh()),
            ],
        ], 201);
    }

    public function updateMemberRole(Request $request, int $memberId): JsonResponse
    {
        $congTy = $this->getCurrentEmployerCompany();
        $user = $this->getAuthenticatedEmployer();

        if (!$congTy || !$user) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn chưa thuộc công ty nào.',
            ], 404);
        }

        if (!$this->isCompanyOwner($user, $congTy)) {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ chủ sở hữu công ty mới có thể cập nhật vai trò HR.',
            ], 403);
        }

        $data = $request->validate([
            'vai_tro_noi_bo' => ['required', 'string', 'in:' . implode(',', array_filter(CongTy::danhSachVaiTroNoiBo(), fn ($role) => $role !== CongTy::VAI_TRO_NOI_BO_OWNER))],
        ], [
            'vai_tro_noi_bo.required' => 'Vui lòng chọn vai trò nội bộ.',
            'vai_tro_noi_bo.in' => 'Vai trò nội bộ không hợp lệ.',
        ]);

        $member = $congTy->thanhViens()
            ->where('nguoi_dungs.id', $memberId)
            ->first();

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy HR trong công ty.',
            ], 404);
        }

        if (($member->pivot?->vai_tro_noi_bo ?? '') === CongTy::VAI_TRO_NOI_BO_OWNER) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể thay đổi vai trò của chủ sở hữu công ty.',
            ], 422);
        }

        $congTy->thanhViens()->updateExistingPivot($memberId, [
            'vai_tro_noi_bo' => $data['vai_tro_noi_bo'],
            'updated_at' => now(),
        ]);
        $this->hrAuditLogService->log(
            $congTy,
            $user,
            'member_role_updated',
            "Cập nhật vai trò nội bộ của {$member->email} thành " . CongTy::nhanVaiTroNoiBo($data['vai_tro_noi_bo']) . '.',
            $member,
            ['vai_tro_noi_bo' => $data['vai_tro_noi_bo']],
        );

        return response()->json([
            'success' => true,
            'message' => 'Đã cập nhật vai trò nội bộ.',
            'data' => [
                'cong_ty' => $this->mapCompanyData($congTy->fresh()),
            ],
        ]);
    }

    public function sendInvitation(Request $request): JsonResponse
    {
        $congTy = $this->getCurrentEmployerCompany();
        $user = $this->getAuthenticatedEmployer();

        if (!$congTy || !$user) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn chưa thuộc công ty nào.',
            ], 404);
        }

        if (!$this->isCompanyOwner($user, $congTy)) {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ owner mới có thể gửi lời mời HR.',
            ], 403);
        }

        $data = $request->validate([
            'email' => ['required', 'email', 'max:150'],
            'vai_tro_noi_bo' => ['nullable', 'string', 'in:' . implode(',', array_filter(CongTy::danhSachVaiTroNoiBo(), fn ($role) => $role !== CongTy::VAI_TRO_NOI_BO_OWNER))],
        ]);

        $email = mb_strtolower(trim($data['email']));
        $member = NguoiDung::whereRaw('LOWER(email) = ?', [$email])->first();

        if ($member && !$member->isNhaTuyenDung()) {
            return response()->json([
                'success' => false,
                'message' => 'Email này thuộc tài khoản không phải nhà tuyển dụng.',
            ], 422);
        }

        if ($member && (int) $member->id === (int) $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không thể tự mời chính mình.',
            ], 422);
        }

        if ($member && $congTy->thanhViens()->where('nguoi_dungs.id', $member->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Tài khoản này đã thuộc công ty rồi.',
            ], 422);
        }

        if ($member) {
            $existingCompany = $member->congTyHienTai();
            if ($existingCompany && (int) $existingCompany->id !== (int) $congTy->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tài khoản nhà tuyển dụng này đã thuộc một công ty khác.',
                ], 422);
            }
        }

        $existingInvite = $congTy->loiMoiThanhViens()
            ->where('email', $email)
            ->where('trang_thai', CongTyLoiMoi::TRANG_THAI_DANG_CHO)
            ->first();

        if ($existingInvite) {
            return response()->json([
                'success' => false,
                'message' => 'Email này đã có lời mời đang chờ xử lý.',
            ], 422);
        }

        $invite = $congTy->loiMoiThanhViens()->create([
            'nguoi_dung_id' => $member?->id,
            'email' => $email,
            'vai_tro_noi_bo' => $data['vai_tro_noi_bo'] ?? CongTy::VAI_TRO_NOI_BO_RECRUITER,
            'trang_thai' => CongTyLoiMoi::TRANG_THAI_DANG_CHO,
            'duoc_moi_boi' => $user->id,
        ]);
        $assignedRole = $data['vai_tro_noi_bo'] ?? CongTy::VAI_TRO_NOI_BO_RECRUITER;
        $this->hrAuditLogService->log(
            $congTy,
            $user,
            'invitation_sent',
            "Gửi lời mời tham gia công ty đến {$email} với vai trò " . CongTy::nhanVaiTroNoiBo($assignedRole) . '.',
            $member,
            [
                'email' => $email,
                'vai_tro_noi_bo' => $assignedRole,
                'da_co_tai_khoan' => (bool) $member,
            ],
        );

        return response()->json([
            'success' => true,
            'message' => 'Đã gửi lời mời tham gia công ty.',
            'data' => [
                'loi_moi' => $this->mapInvitationData($invite),
                'cong_ty' => $this->mapCompanyData($congTy->fresh()),
            ],
        ], 201);
    }

    public function cancelInvitation(int $inviteId): JsonResponse
    {
        $congTy = $this->getCurrentEmployerCompany();
        $user = $this->getAuthenticatedEmployer();

        if (!$congTy || !$user) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn chưa thuộc công ty nào.',
            ], 404);
        }

        if (!$this->isCompanyOwner($user, $congTy)) {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ owner mới có thể hủy lời mời HR.',
            ], 403);
        }

        $invite = $congTy->loiMoiThanhViens()
            ->whereKey($inviteId)
            ->where('trang_thai', CongTyLoiMoi::TRANG_THAI_DANG_CHO)
            ->first();

        if (!$invite) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy lời mời đang chờ xử lý.',
            ], 404);
        }

        $invite->update([
            'trang_thai' => CongTyLoiMoi::TRANG_THAI_DA_HUY,
            'phan_hoi_luc' => now(),
        ]);
        $this->hrAuditLogService->log(
            $congTy,
            $user,
            'invitation_canceled',
            "Hủy lời mời tham gia công ty dành cho {$invite->email}.",
            $invite->nguoiDung,
            ['email' => $invite->email],
        );

        return response()->json([
            'success' => true,
            'message' => 'Đã hủy lời mời HR.',
            'data' => [
                'cong_ty' => $this->mapCompanyData($congTy->fresh()),
            ],
        ]);
    }

    public function receivedInvitations(): JsonResponse
    {
        $user = $this->getAuthenticatedEmployer();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Phiên đăng nhập không hợp lệ.',
            ], 401);
        }

        $invites = CongTyLoiMoi::query()
            ->with(['congTy:id,ten_cong_ty', 'nguoiMoi:id,ho_ten,email'])
            ->where('email', $user->email)
            ->where('trang_thai', CongTyLoiMoi::TRANG_THAI_DANG_CHO)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (CongTyLoiMoi $invite) => $this->mapInvitationData($invite))
            ->values();

        return response()->json([
            'success' => true,
            'data' => $invites,
        ]);
    }

    public function respondToInvitation(Request $request, int $inviteId): JsonResponse
    {
        $user = $this->getAuthenticatedEmployer();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Phiên đăng nhập không hợp lệ.',
            ], 401);
        }

        $data = $request->validate([
            'hanh_dong' => ['required', 'string', 'in:accept,reject'],
        ], [
            'hanh_dong.required' => 'Thiếu hành động phản hồi lời mời.',
            'hanh_dong.in' => 'Hành động phản hồi không hợp lệ.',
        ]);

        $invite = CongTyLoiMoi::query()
            ->with('congTy')
            ->whereKey($inviteId)
            ->where('email', $user->email)
            ->where('trang_thai', CongTyLoiMoi::TRANG_THAI_DANG_CHO)
            ->first();

        if (!$invite) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy lời mời hợp lệ.',
            ], 404);
        }

        if ($data['hanh_dong'] === 'accept') {
            if ($user->congTyHienTai()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn đã thuộc một công ty khác, không thể chấp nhận lời mời này.',
                ], 422);
            }

            $invite->congTy->thanhViens()->syncWithoutDetaching([
                $user->id => [
                    'vai_tro_noi_bo' => $invite->vai_tro_noi_bo,
                    'duoc_tao_boi' => $invite->duoc_moi_boi,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);

            $invite->update([
                'nguoi_dung_id' => $user->id,
                'trang_thai' => CongTyLoiMoi::TRANG_THAI_CHAP_NHAN,
                'phan_hoi_luc' => now(),
            ]);
            $this->hrAuditLogService->log(
                $invite->congTy,
                $user,
                'invitation_accepted',
                "{$user->email} đã chấp nhận lời mời tham gia công ty.",
                $user,
                ['vai_tro_noi_bo' => $invite->vai_tro_noi_bo],
            );

            return response()->json([
                'success' => true,
                'message' => 'Đã chấp nhận lời mời tham gia công ty.',
                'data' => [
                    'cong_ty' => $this->mapCompanyData($invite->congTy->fresh()),
                ],
            ]);
        }

        $invite->update([
            'nguoi_dung_id' => $user->id,
            'trang_thai' => CongTyLoiMoi::TRANG_THAI_TU_CHOI,
            'phan_hoi_luc' => now(),
        ]);
        $this->hrAuditLogService->log(
            $invite->congTy,
            $user,
            'invitation_rejected',
            "{$user->email} đã từ chối lời mời tham gia công ty.",
            $user,
        );

        return response()->json([
            'success' => true,
            'message' => 'Đã từ chối lời mời tham gia công ty.',
        ]);
    }

    public function removeMember(int $memberId): JsonResponse
    {
        $congTy = $this->getCurrentEmployerCompany();
        $user = $this->getAuthenticatedEmployer();

        if (!$congTy || !$user) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn chưa thuộc công ty nào.',
            ], 404);
        }

        if (!$this->isCompanyOwner($user, $congTy)) {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ chủ sở hữu công ty mới có thể quản lý thành viên HR.',
            ], 403);
        }

        $member = $congTy->thanhViens()
            ->where('nguoi_dungs.id', $memberId)
            ->first();

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy HR trong công ty.',
            ], 404);
        }

        if (($member->pivot?->vai_tro_noi_bo ?? '') === CongTy::VAI_TRO_NOI_BO_OWNER) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể gỡ chủ sở hữu công ty khỏi danh sách thành viên.',
            ], 422);
        }

        $congTy->thanhViens()->detach($memberId);
        $this->hrAuditLogService->log(
            $congTy,
            $user,
            'member_removed',
            "Gỡ {$member->email} khỏi công ty.",
            $member,
        );

        return response()->json([
            'success' => true,
            'message' => 'Đã gỡ HR khỏi công ty.',
            'data' => [
                'cong_ty' => $this->mapCompanyData($congTy->fresh()),
            ],
        ]);
    }

    public function hrAuditLogs(Request $request): JsonResponse
    {
        $congTy = $this->getCurrentEmployerCompany();

        if (!$congTy) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn chưa thuộc công ty nào.',
            ], 404);
        }

        $logs = AuditLog::query()
            ->where('company_id', $congTy->id)
            ->where('metadata_json->scope', 'hr')
            ->with(['actor:id,ho_ten,email'])
            ->latest()
            ->paginate((int) $request->get('per_page', 10));

        $logs->setCollection(
            $logs->getCollection()->map(fn (AuditLog $log) => $this->mapHrAuditLogData($log))
        );

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }
}
