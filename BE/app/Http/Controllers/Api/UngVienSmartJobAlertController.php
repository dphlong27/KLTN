<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SmartJobAlert;
use App\Services\SmartJobAlertService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UngVienSmartJobAlertController extends Controller
{
    public function __construct(private readonly SmartJobAlertService $smartJobAlertService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = SmartJobAlert::query()
            ->with([
                'tinTuyenDung.congTy:id,ten_cong_ty,logo',
                'congTy:id,ten_cong_ty,logo',
                'hoSo:id,tieu_de_ho_so,vi_tri_ung_tuyen_muc_tieu',
            ])
            ->where('nguoi_dung_id', $user->id)
            ->latest();

        if ($request->filled('match_level')) {
            $query->where('match_level', $request->input('match_level'));
        }

        if ($request->filled('trang_thai')) {
            $query->where('trang_thai', $request->input('trang_thai'));
        } else {
            $query->where('trang_thai', '!=', SmartJobAlert::TRANG_THAI_BO_QUA);
        }

        if ($request->filled('min_score')) {
            $query->where('match_score', '>=', (float) $request->input('min_score'));
        }

        $alerts = $query->paginate(min((int) $request->get('per_page', 12), 50));
        $alerts->setCollection($alerts->getCollection()->map(fn (SmartJobAlert $alert) => $this->smartJobAlertService->mapAlert($alert)));

        return response()->json([
            'success' => true,
            'data' => $alerts,
        ]);
    }

    public function stats(Request $request): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $base = SmartJobAlert::query()->where('nguoi_dung_id', $userId);

        return response()->json([
            'success' => true,
            'data' => [
                'total' => (clone $base)->where('trang_thai', '!=', SmartJobAlert::TRANG_THAI_BO_QUA)->count(),
                'new' => (clone $base)->where('trang_thai', SmartJobAlert::TRANG_THAI_MOI)->count(),
                'strong' => (clone $base)->whereIn('match_level', ['strong', 'excellent'])->where('trang_thai', '!=', SmartJobAlert::TRANG_THAI_BO_QUA)->count(),
                'average_score' => round((float) ((clone $base)->where('trang_thai', '!=', SmartJobAlert::TRANG_THAI_BO_QUA)->avg('match_score') ?? 0), 1),
            ],
        ]);
    }

    public function markAsRead(Request $request, int $id): JsonResponse
    {
        $alert = SmartJobAlert::where('nguoi_dung_id', $request->user()->id)->findOrFail($id);

        if ($alert->trang_thai !== SmartJobAlert::TRANG_THAI_BO_QUA) {
            $alert->forceFill([
                'trang_thai' => SmartJobAlert::TRANG_THAI_DA_DOC,
                'read_at' => $alert->read_at ?? now(),
            ])->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã đánh dấu alert là đã đọc.',
            'data' => $this->smartJobAlertService->mapAlert($alert->fresh(['tinTuyenDung.congTy', 'congTy', 'hoSo'])),
        ]);
    }

    public function dismiss(Request $request, int $id): JsonResponse
    {
        $alert = SmartJobAlert::where('nguoi_dung_id', $request->user()->id)->findOrFail($id);
        $alert->forceFill([
            'trang_thai' => SmartJobAlert::TRANG_THAI_BO_QUA,
            'dismissed_at' => now(),
        ])->save();

        return response()->json([
            'success' => true,
            'message' => 'Đã bỏ qua smart alert.',
        ]);
    }
}
