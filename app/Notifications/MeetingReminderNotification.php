<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Meeting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class MeetingReminderNotification extends Notification
{
    use Queueable;

    public Meeting $meeting;

    public string $timing;

    /**
     * Create a new notification instance.
     */
    public function __construct(Meeting $meeting, string $timing)
    {
        $this->meeting = $meeting;
        $this->timing = $timing;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $data = $this->toArray($notifiable);

        return (new MailMessage)
            ->subject("【リマインド】{$data['title']}")
            ->greeting("{$notifiable->name} 様")
            ->line($data['message'])
            ->action('面談画面を開く', $data['url'])
            ->line('時間になりましたら、上記リンクよりご参加ください。');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        // 日本語の曜日配列を定義
        $weekdays = ['日', '月', '火', '水', '木', '金', '土'];

        /** @var Carbon $startAt */
        $startAt = Carbon::parse($this->meeting->scheduled_at);
        $endAt = $startAt->copy()->addMinutes(60); // 60分間

        // 日時・曜日・時間帯のフォーマット
        $dateStr = $startAt->format('Y年n月j日');
        $dayOfWeek = $weekdays[$startAt->dayOfWeek];
        $timeRange = $startAt->format('H:i').'〜'.$endAt->format('H:i');
        $formattedSchedule = "{$dateStr}({$dayOfWeek}) {$timeRange}";

        [$title, $message] = match ($this->timing) {
            'before_one_days' => [
                '明日の面談のお知らせです',
                "{$formattedSchedule}です。ご確認をお願い致します",
            ],
            'before_one_hours' => [
                '面談のお知らせ',
                "{$formattedSchedule}です。お時間になりましたら、入室の程お願い致します",
            ],
            default => [
                '【リマインド】面談のお知らせ',
                '予定されている面談のお知らせです。',
            ],
        };

        return [
            'title' => $title,
            'message' => $message,
            'url' => route('meetings.show', $this->meeting),
        ];
    }
}
