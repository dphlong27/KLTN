<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UngTuyen\CapNhatTrangThaiRequest;
use App\Http\Requests\UngTuyen\GuiOfferRequest;
use App\Models\UngTuyen;
use App\Notifications\ApplicationStatusNotification;
use App\Notifications\InterviewScheduledNotification;
use App\Notifications\OfferLetterNotification;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class NhaTuyenDungUngTuyenController extends Controller
{
    private function nowUtc(): Carbon
    {
        return Carbon::now('Asia/Ho_Chi_Minh')->utc();
    }

    private function isFinalStatus(UngTuyen $ungTuyen): bool
    {
        return in_array((int) $ungTuyen->trang_thai, UngTuyen::TRANG_THAI_CUOI, true);
    }

    private function isOfferManagedStatus(int $status): bool
    {
        return in_array($status, [
            UngTuyen::TRANG_THAI_DA_GUI_OFFER,
            UngTuyen::TRANG_THAI_DA_NHAN_VIEC,
            UngTuyen::TRANG_THAI_TU_CHOI_OFFER,
        ], true);
    }

    private function appendHistory(UngTuyen $ungTuyen, string $event, string $message, array $meta = []): void
    {
        $actor = auth()->user();

        $ungTuyen->appendHistory([
            'event' => $event,
            'message' => $message,
            'actor' => [
                'type' => 'employer',
                'id' => $actor?->id,
                'name' => $actor?->ho_ten ?: $actor?->email ?: 'Nhà tuyển dụng',
            ],
            'meta' => $meta,
        ]);
    }

    private function shouldSendInterviewNotification(UngTuyen $ungTuyen, array $dataUpdate): bool
    {
        $incomingDate = $dataUpdate['ngay_hen_phong_van'] ?? null;
        $incomingMethod = $dataUpdate['hinh_thuc_phong_van'] ?? null;
        $incomingInterviewer = $dataUpdate['nguoi_phong_van'] ?? null;
        $incomingLink = $dataUpdate['link_phong_van'] ?? null;
        $incomingRound = $dataUpdate['vong_phong_van_hien_tai'] ?? null;

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
            || (string) ($ungTuyen->link_phong_van ?? '') !== (string) ($incomingLink ?? '')
            || (string) ($ungTuyen->vong_phong_van_hien_tai ?? '') !== (string) ($incomingRound ?? '');
    }

    private function dispatchInterviewNotification(UngTuyen $ungTuyen, string $mode = 'scheduled'): void
    {
        $ungTuyen->loadMissing([
            'tinTuyenDung.congTy',
            'hoSo.nguoiDung',
        ]);

        $ungVien = $ungTuyen->hoSo?->nguoiDung;

        if (!$ungVien || !$ungVien->email) {
            return;
        }

        dispatch(function () use ($ungVien, $ungTuyen, $mode): void {
            $ungTuyenFresh = $ungTuyen->fresh(['tinTuyenDung.congTy', 'hoSo.nguoiDung']);
            $ungVien->notify(new InterviewScheduledNotification($ungTuyenFresh, $mode));
        })->afterResponse();
    }

    private function dispatchStatusNotification(UngTuyen $ungTuyen): void
    {
        $ungTuyen->loadMissing([
            'tinTuyenDung.congTy',
            'hoSo.nguoiDung',
        ]);

        $ungVien = $ungTuyen->hoSo?->nguoiDung;

        if (!$ungVien || !$ungVien->email) {
            return;
        }

        dispatch(function () use ($ungVien, $ungTuyen): void {
            $ungTuyenFresh = $ungTuyen->fresh(['tinTuyenDung.congTy', 'hoSo.nguoiDung']);
            $ungVien->notify(new ApplicationStatusNotification($ungTuyenFresh));
        })->afterResponse();
    }

    private function dispatchOfferNotification(UngTuyen $ungTuyen): void
    {
        $ungTuyen->loadMissing([
            'tinTuyenDung.congTy',
            'hoSo.nguoiDung',
        ]);

        $ungVien = $ungTuyen->hoSo?->nguoiDung;

        if (!$ungVien || !$ungVien->email) {
            return;
        }

        dispatch(function () use ($ungVien, $ungTuyen): void {
            $ungTuyenFresh = $ungTuyen->fresh(['tinTuyenDung.congTy', 'hoSo.nguoiDung']);
            $ungVien->notify(new OfferLetterNotification($ungTuyenFresh));
        })->afterResponse();
    }

    private function buildCalendarContent(UngTuyen $ungTuyen): string
    {
        $ungTuyen->loadMissing(['tinTuyenDung.congTy', 'hoSo.nguoiDung']);

        $start = $ungTuyen->ngay_hen_phong_van?->copy()->utc();
        $end = $start?->copy()->addHour();
        $jobTitle = $ungTuyen->tinTuyenDung?->tieu_de ?: 'Phỏng vấn';
        $companyName = $ungTuyen->tinTuyenDung?->congTy?->ten_cong_ty ?: 'Doanh nghiệp';
        $candidateName = $ungTuyen->hoSo?->nguoiDung?->ho_ten ?: 'Ứng viên';
        $roundLabel = UngTuyen::getVongPhongVanLabel($ungTuyen->vong_phong_van_hien_tai);

        $escape = static fn (?string $value): string => str_replace(
            ["\\", ";", ",", "\r", "\n"],
            ["\\\\", "\\;", "\\,", '', '\\n'],
            (string) $value
        );

        return implode("\r\n", [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//AIRecruitment//Interview Calendar//VI',
            'BEGIN:VEVENT',
            'UID:application-' . $ungTuyen->id . '@airecruitment.local',
            'DTSTAMP:' . now('UTC')->format('Ymd\THis\Z'),
            'DTSTART:' . ($start?->format('Ymd\THis\Z') ?? now('UTC')->format('Ymd\THis\Z')),
            'DTEND:' . ($end?->format('Ymd\THis\Z') ?? now('UTC')->addHour()->format('Ymd\THis\Z')),
            'SUMMARY:' . $escape($roundLabel . ' - ' . $jobTitle . ' - ' . $companyName),
            'DESCRIPTION:' . $escape("Ứng viên: {$candidateName}\nNgười phỏng vấn: " . ($ungTuyen->nguoi_phong_van ?: 'Đang cập nhật')),
            'LOCATION:' . $escape($ungTuyen->link_phong_van ?: 'Đang cập nhật'),
            'END:VEVENT',
            'END:VCALENDAR',
            '',
        ]);
    }

    /**
     * Xem danh sách CV ứng viên đã nộp vào CÔNG TY CỦA ĐANG ĐĂNG NHẬP
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        $congTy = $user->congTy;

        if (!$congTy) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng thiết lập thông tin công ty trước.'
            ], 403);
        }

        $query = UngTuyen::whereHas('tinTuyenDung', function ($q) use ($congTy) {
            $q->where('cong_ty_id', $congTy->id);
        })->with([
            'tinTuyenDung' => function ($q) {
                $q->select('id', 'tieu_de', 'hinh_thuc_lam_viec', 'trang_thai', 'so_luong_tuyen')
                    ->withCount([
                        'acceptedApplications as so_luong_da_nhan',
                    ]);
            },
            'hoSo' => function ($q) {
                $q->withTrashed()
                    ->select('id', 'nguoi_dung_id', 'tieu_de_ho_so', 'muc_tieu_nghe_nghiep', 'file_cv')
                    ->with('nguoiDung:id,ho_ten,email');
            }
        ]);

        if ($request->has('tin_tuyen_dung_id') && $request->tin_tuyen_dung_id !== '') {
            $query->where('tin_tuyen_dung_id', $request->tin_tuyen_dung_id);
        }

        if ($request->has('trang_thai') && $request->trang_thai !== '') {
            $query->where('trang_thai', $request->trang_thai);
        }

        $query->orderBy('thoi_gian_ung_tuyen', 'desc');

        $ungTuyens = $query->paginate((int) $request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $ungTuyens
        ]);
    }

    /**
     * Đổi trạng thái xử lý đơn ứng tuyển
     */
    public function updateTrangThai(CapNhatTrangThaiRequest $request, $id): JsonResponse
    {
        $user = auth()->user();
        $congTy = $user->congTy;

        if (!$congTy) {
            return response()->json(['success' => false, 'message' => 'Lỗi công ty'], 403);
        }

        $ungTuyen = UngTuyen::whereHas('tinTuyenDung', function ($q) use ($congTy) {
            $q->where('cong_ty_id', $congTy->id);
        })->findOrFail($id);

        $trangThaiCu = (int) $ungTuyen->trang_thai;
        $trangThaiMoi = (int) $request->trang_thai;

        if ($this->isOfferManagedStatus($trangThaiMoi)) {
            return response()->json([
                'success' => false,
                'message' => 'Trạng thái offer phải được xử lý qua luồng gửi offer hoặc phản hồi offer.',
            ], 422);
        }

        if ($ungTuyen->da_rut_don) {
            return response()->json([
                'success' => false,
                'message' => 'Ứng viên đã rút đơn ứng tuyển nên không thể cập nhật xử lý nữa.',
            ], 422);
        }

        if ($this->isFinalStatus($ungTuyen) && $trangThaiCu !== $trangThaiMoi) {
            return response()->json([
                'success' => false,
                'message' => 'Đơn ứng tuyển đã có kết quả cuối nên không thể đổi sang trạng thái khác.',
            ], 422);
        }

        if (
            $trangThaiMoi === UngTuyen::TRANG_THAI_TRUNG_TUYEN
            && $trangThaiCu !== UngTuyen::TRANG_THAI_TRUNG_TUYEN
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
            'trang_thai' => $trangThaiMoi,
        ];

        foreach ([
            'ngay_hen_phong_van',
            'hinh_thuc_phong_van',
            'vong_phong_van_hien_tai',
            'nguoi_phong_van',
            'link_phong_van',
            'ket_qua_phong_van',
            'rubric_danh_gia_phong_van',
            'ghi_chu',
        ] as $field) {
            if ($request->has($field)) {
                $dataUpdate[$field] = $request->input($field);
            }
        }

        if ($request->filled('ngay_hen_phong_van') && empty($dataUpdate['vong_phong_van_hien_tai'])) {
            $dataUpdate['vong_phong_van_hien_tai'] = $ungTuyen->vong_phong_van_hien_tai ?: UngTuyen::VONG_PHONG_VAN_HR;
        }

        $shouldNotifyInterview = $this->shouldSendInterviewNotification($ungTuyen, $dataUpdate);
        $shouldNotifyStatus = $trangThaiCu !== $trangThaiMoi && in_array($trangThaiMoi, [
            UngTuyen::TRANG_THAI_TRUNG_TUYEN,
            UngTuyen::TRANG_THAI_TU_CHOI,
        ], true);

        if ($request->has('ngay_hen_phong_van')) {
            if (empty($dataUpdate['ngay_hen_phong_van'])) {
                $dataUpdate['trang_thai_tham_gia_phong_van'] = null;
                $dataUpdate['thoi_gian_phan_hoi_phong_van'] = null;
                $dataUpdate['thoi_gian_gui_nhac_lich'] = null;
            } elseif ($shouldNotifyInterview) {
                $dataUpdate['trang_thai_tham_gia_phong_van'] = UngTuyen::PHONG_VAN_CHO_XAC_NHAN;
                $dataUpdate['thoi_gian_phan_hoi_phong_van'] = null;
                $dataUpdate['thoi_gian_gui_nhac_lich'] = null;
            }
        }

        $oldSchedule = $ungTuyen->ngay_hen_phong_van?->format('Y-m-d H:i:s');
        $oldRound = $ungTuyen->vong_phong_van_hien_tai;
        $oldRubric = (string) ($ungTuyen->rubric_danh_gia_phong_van ?? '');

        $ungTuyen->fill($dataUpdate);

        if ($trangThaiCu !== $trangThaiMoi) {
            $this->appendHistory(
                $ungTuyen,
                'status_updated',
                'Cập nhật trạng thái đơn ứng tuyển.',
                [
                    'from' => UngTuyen::getTrangThaiLabel($trangThaiCu),
                    'to' => UngTuyen::getTrangThaiLabel($trangThaiMoi),
                ]
            );
        }

        if ($shouldNotifyInterview) {
            $this->appendHistory(
                $ungTuyen,
                $oldSchedule ? 'interview_rescheduled' : 'interview_scheduled',
                $oldSchedule ? 'Đã cập nhật lịch phỏng vấn.' : 'Đã lên lịch phỏng vấn.',
                [
                    'round' => UngTuyen::getVongPhongVanLabel($ungTuyen->vong_phong_van_hien_tai),
                    'time' => $ungTuyen->ngay_hen_phong_van?->toISOString(),
                    'method' => $ungTuyen->hinh_thuc_phong_van,
                ]
            );
        } elseif ($oldRound !== $ungTuyen->vong_phong_van_hien_tai && $ungTuyen->vong_phong_van_hien_tai) {
            $this->appendHistory(
                $ungTuyen,
                'interview_round_updated',
                'Đã cập nhật vòng phỏng vấn.',
                [
                    'from' => UngTuyen::getVongPhongVanLabel($oldRound),
                    'to' => UngTuyen::getVongPhongVanLabel($ungTuyen->vong_phong_van_hien_tai),
                ]
            );
        }

        if ($request->has('rubric_danh_gia_phong_van') && $oldRubric !== (string) ($ungTuyen->rubric_danh_gia_phong_van ?? '')) {
            $this->appendHistory(
                $ungTuyen,
                'interview_rubric_updated',
                'Đã cập nhật rubric đánh giá phỏng vấn.'
            );
        }

        $ungTuyen->save();

        if ($shouldNotifyInterview) {
            $this->dispatchInterviewNotification($ungTuyen, $oldSchedule ? 'rescheduled' : 'scheduled');
        } elseif ($shouldNotifyStatus) {
            $this->dispatchStatusNotification($ungTuyen);
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
                        ]);
                },
                'hoSo' => function ($q) {
                    $q->withTrashed()->select('id', 'nguoi_dung_id', 'tieu_de_ho_so', 'file_cv')->with('nguoiDung:id,ho_ten,email');
                },
            ])
        ]);
    }

    public function guiLaiEmailPhongVan(Request $request, $id): JsonResponse
    {
        $user = auth()->user();
        $congTy = $user->congTy;

        if (!$congTy) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng thiết lập thông tin công ty trước.'
            ], 403);
        }

        $ungTuyen = UngTuyen::whereHas('tinTuyenDung', function ($q) use ($congTy) {
            $q->where('cong_ty_id', $congTy->id);
        })->with(['tinTuyenDung.congTy', 'hoSo.nguoiDung'])->findOrFail($id);

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

        $this->appendHistory($ungTuyen, 'interview_email_resent', 'Đã gửi lại email lịch phỏng vấn.');
        $ungTuyen->save();
        $this->dispatchInterviewNotification($ungTuyen, 'rescheduled');

        return response()->json([
            'success' => true,
            'message' => 'Đã gửi lại email lịch phỏng vấn cho ứng viên.',
        ]);
    }

    public function guiNhacLichPhongVan(Request $request, $id): JsonResponse
    {
        $user = auth()->user();
        $congTy = $user->congTy;

        if (!$congTy) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng thiết lập thông tin công ty trước.'
            ], 403);
        }

        $ungTuyen = UngTuyen::whereHas('tinTuyenDung', function ($q) use ($congTy) {
            $q->where('cong_ty_id', $congTy->id);
        })->with(['tinTuyenDung.congTy', 'hoSo.nguoiDung'])->findOrFail($id);

        if (!$ungTuyen->ngay_hen_phong_van || $ungTuyen->ngay_hen_phong_van->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể gửi nhắc lịch cho buổi phỏng vấn không còn hiệu lực.',
            ], 422);
        }

        if ($ungTuyen->da_rut_don || $this->isFinalStatus($ungTuyen)) {
            return response()->json([
                'success' => false,
                'message' => 'Đơn ứng tuyển này không còn phù hợp để gửi nhắc lịch.',
            ], 422);
        }

        $ungTuyen->thoi_gian_gui_nhac_lich = $this->nowUtc();
        $this->appendHistory($ungTuyen, 'interview_reminder_sent', 'Đã gửi email nhắc lịch phỏng vấn.');
        $ungTuyen->save();
        $this->dispatchInterviewNotification($ungTuyen, 'reminder');

        return response()->json([
            'success' => true,
            'message' => 'Đã gửi email nhắc lịch phỏng vấn cho ứng viên.',
        ]);
    }

    public function guiOffer(GuiOfferRequest $request, int $id): JsonResponse
    {
        $user = auth()->user();
        $congTy = $user->congTy;

        if (!$congTy) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng thiết lập thông tin công ty trước.'
            ], 403);
        }

        $ungTuyen = UngTuyen::whereHas('tinTuyenDung', function ($q) use ($congTy) {
            $q->where('cong_ty_id', $congTy->id);
        })->with(['tinTuyenDung.congTy', 'hoSo.nguoiDung'])->findOrFail($id);

        if ($ungTuyen->da_rut_don) {
            return response()->json([
                'success' => false,
                'message' => 'Ứng viên đã rút đơn nên không thể gửi offer.',
            ], 422);
        }

        if (in_array((int) $ungTuyen->trang_thai, [
            UngTuyen::TRANG_THAI_DA_NHAN_VIEC,
            UngTuyen::TRANG_THAI_TU_CHOI,
            UngTuyen::TRANG_THAI_TU_CHOI_OFFER,
        ], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Đơn ứng tuyển này đã khép lại nên không thể gửi offer.',
            ], 422);
        }

        if (!in_array((int) $ungTuyen->trang_thai, [
            UngTuyen::TRANG_THAI_TRUNG_TUYEN,
            UngTuyen::TRANG_THAI_DA_GUI_OFFER,
        ], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ có thể gửi offer cho ứng viên đã ở trạng thái trúng tuyển.',
            ], 422);
        }

        $isResend = (int) $ungTuyen->trang_thai === UngTuyen::TRANG_THAI_DA_GUI_OFFER;

        $ungTuyen->fill([
            'trang_thai' => UngTuyen::TRANG_THAI_DA_GUI_OFFER,
            'ghi_chu_offer' => $request->input('ghi_chu_offer'),
            'link_offer' => $request->input('link_offer'),
            'thoi_gian_gui_offer' => $this->nowUtc(),
            'thoi_gian_phan_hoi_offer' => null,
        ]);

        $this->appendHistory(
            $ungTuyen,
            $isResend ? 'offer_resent' : 'offer_sent',
            $isResend ? 'Đã gửi lại offer cho ứng viên.' : 'Đã gửi offer cho ứng viên.',
            [
                'link_offer' => $request->input('link_offer'),
            ]
        );

        $ungTuyen->save();
        $this->dispatchOfferNotification($ungTuyen);

        return response()->json([
            'success' => true,
            'message' => $isResend ? 'Đã gửi lại offer cho ứng viên.' : 'Đã gửi offer cho ứng viên.',
            'data' => $ungTuyen->fresh([
                'tinTuyenDung.congTy',
                'hoSo.nguoiDung',
            ]),
        ]);
    }

    public function xuatLichPhongVan(Request $request, int $id): Response
    {
        $user = auth()->user();
        $congTy = $user->congTy;

        if (!$congTy) {
            abort(403, 'Vui lòng thiết lập thông tin công ty trước.');
        }

        $ungTuyen = UngTuyen::whereHas('tinTuyenDung', function ($q) use ($congTy) {
            $q->where('cong_ty_id', $congTy->id);
        })->with(['tinTuyenDung.congTy', 'hoSo.nguoiDung'])->findOrFail($id);

        if (!$ungTuyen->ngay_hen_phong_van) {
            abort(422, 'Ứng tuyển này chưa có lịch phỏng vấn để xuất calendar.');
        }

        $filename = 'interview-' . $ungTuyen->id . '.ics';
        $content = $this->buildCalendarContent($ungTuyen);

        return response($content, 200, [
            'Content-Type' => 'text/calendar; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
