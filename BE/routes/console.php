<?php

use App\Models\UngTuyen;
use App\Notifications\InterviewScheduledNotification;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('interviews:send-reminders', function () {
    $now = now('UTC');
    $windowEnd = $now->copy()->addDay();

    $applications = UngTuyen::query()
        ->whereNotNull('ngay_hen_phong_van')
        ->whereNull('thoi_gian_gui_nhac_lich')
        ->where('da_rut_don', false)
        ->whereNotIn('trang_thai', UngTuyen::TRANG_THAI_CUOI)
        ->whereBetween('ngay_hen_phong_van', [$now, $windowEnd])
        ->with(['tinTuyenDung.congTy', 'hoSo.nguoiDung'])
        ->get();

    $sent = 0;

    foreach ($applications as $application) {
        $candidate = $application->hoSo?->nguoiDung;

        if (!$candidate || !$candidate->email) {
            continue;
        }

        $candidate->notify(new InterviewScheduledNotification($application, 'reminder'));
        $application->thoi_gian_gui_nhac_lich = $now;
        $application->appendHistory([
            'event' => 'interview_reminder_sent',
            'message' => 'Hệ thống đã tự động gửi email nhắc lịch phỏng vấn.',
            'actor' => [
                'type' => 'system',
                'id' => null,
                'name' => 'System',
            ],
            'meta' => [],
        ]);
        $application->save();
        $sent++;
    }

    $this->info("Đã gửi {$sent} email nhắc lịch phỏng vấn.");
})->purpose('Send interview reminder emails for upcoming interviews');

Schedule::command('interviews:send-reminders')->hourly();
