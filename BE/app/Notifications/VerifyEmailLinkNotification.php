<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class VerifyEmailLinkNotification extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1((string) $notifiable->getEmailForVerification()),
            ]
        );

        return (new MailMessage)
            ->subject('Xác thực email tài khoản')
            ->greeting('Xin chào ' . ($notifiable->ho_ten ?: 'bạn') . '!')
            ->line('Cảm ơn bạn đã đăng ký tài khoản trên hệ thống AI Recruitment.')
            ->line('Vui lòng xác thực email để kích hoạt tài khoản và bắt đầu sử dụng đầy đủ các chức năng.')
            ->action('Xác thực email', $verificationUrl)
            ->line('Liên kết này sẽ hết hạn sau 60 phút.')
            ->line('Nếu bạn không tạo tài khoản này, bạn có thể bỏ qua email.');
    }
}
