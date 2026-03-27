<?php

namespace App\Notifications;

use App\Models\UngTuyen;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InterviewScheduledNotification extends Notification
{
    use Queueable;

    private const DISPLAY_TIMEZONE = 'Asia/Ho_Chi_Minh';
    private const FRONTEND_FALLBACK = 'http://localhost:5173';

    public function __construct(
        private readonly UngTuyen $ungTuyen,
        private readonly bool $isRescheduled = false,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $ungTuyen = $this->ungTuyen;
        $tin = $ungTuyen->tinTuyenDung;
        $congTy = $tin?->congTy;
        $tenViTri = $tin?->tieu_de ?: 'Chưa xác định';
        $tenCongTy = $congTy?->ten_cong_ty ?: 'Chưa xác định';
        $ngayHen = $ungTuyen->ngay_hen_phong_van;
        $hinhThuc = match ((string) ($ungTuyen->hinh_thuc_phong_van ?? '')) {
            'online' => 'Phỏng vấn online',
            'offline' => 'Phỏng vấn trực tiếp',
            'phone' => 'Phỏng vấn qua điện thoại',
            default => null,
        };
        $nguoiPhongVan = trim((string) ($ungTuyen->nguoi_phong_van ?? ''));
        $linkPhongVan = trim((string) ($ungTuyen->link_phong_van ?? ''));
        $thoiGian = $ngayHen
            ? $ngayHen->timezone(self::DISPLAY_TIMEZONE)->format('H:i d/m/Y')
            : 'Chưa xác định';
        $subject = $this->isRescheduled
            ? "Cap nhat lich phong van - {$tenViTri} tai {$tenCongTy}"
            : "Thu moi phong van - {$tenViTri} tai {$tenCongTy}";
        $previewText = $this->isRescheduled
            ? 'Nha tuyen dung vua cap nhat lich phong van cua ban.'
            : 'Nha tuyen dung vua dat lich phong van cho ho so ung tuyen cua ban.';

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.interview-scheduled', [
                'subjectText' => $subject,
                'previewText' => $previewText,
                'isRescheduled' => $this->isRescheduled,
                'candidateName' => $notifiable->ho_ten ?: 'bạn',
                'jobTitle' => $tenViTri,
                'companyName' => $tenCongTy,
                'interviewTime' => $thoiGian,
                'interviewMode' => $hinhThuc,
                'interviewerName' => $nguoiPhongVan,
                'locationOrLink' => $linkPhongVan,
                'actionUrl' => rtrim((string) env('FRONTEND_URL', self::FRONTEND_FALLBACK), '/') . '/applications',
            ]);
    }
}
