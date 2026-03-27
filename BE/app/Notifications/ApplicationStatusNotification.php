<?php

namespace App\Notifications;

use App\Models\UngTuyen;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationStatusNotification extends Notification
{
    use Queueable;

    private const FRONTEND_FALLBACK = 'http://localhost:5173';

    public function __construct(
        private readonly UngTuyen $ungTuyen,
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
        $isAccepted = (int) $ungTuyen->trang_thai === UngTuyen::TRANG_THAI_CHAP_NHAN;
        $frontEndUrl = rtrim((string) env('FRONTEND_URL', self::FRONTEND_FALLBACK), '/');
        $tenNguoiNhan = $notifiable->ho_ten ?: 'bạn';
        $tenViTri = $tin?->tieu_de ?: 'Chưa xác định';
        $tenCongTy = $congTy?->ten_cong_ty ?: 'Chưa xác định';
        $subject = $isAccepted
            ? "Chuc mung! Ban da vuot qua vong ho so - {$tenViTri} tai {$tenCongTy}"
            : "Ket qua ung tuyen - {$tenViTri} tai {$tenCongTy}";
        $previewText = $isAccepted
            ? 'Ho so cua ban da duoc chap nhan va da vuot qua vong xet duyet hien tai.'
            : 'Ho so cua ban hien chua phu hop voi nhu cau tuyen dung o vong nay.';

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.application-status', [
                'subjectText' => $subject,
                'previewText' => $previewText,
                'isAccepted' => $isAccepted,
                'candidateName' => $tenNguoiNhan,
                'jobTitle' => $tenViTri,
                'companyName' => $tenCongTy,
                'actionUrl' => $frontEndUrl . '/applications',
            ]);
    }
}
