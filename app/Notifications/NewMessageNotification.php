<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Announcement;
use App\Models\ChatMessage;
use App\Models\ChatRoom;
use App\Models\Meeting;
use App\Models\QaReply;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Str;

class NewMessageNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Model $source,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    // メール通知の内容
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->buildTitle())
            ->line($this->buildBody())
            ->action('画面を確認する', $this->buildUrl());
    }

    // アプリ内通知（database）として保存されるJSONデータ

    public function toArray(object $notifiable): array
    {
        $fullBody = $this->source instanceof Announcement 
        ? $this->source->body 
        : $this->buildBody();
        
        return [
            'title' => $this->buildTitle(),
            'message' => $this->buildBody(),
            'body' => $fullBody,
            'url' => $this->buildUrl(),
        ];
    }

    /**
     * タイトルの生成 (instanceof を使って型判定)
     */
    private function buildTitle(): string
    {
        return match (true) {
            $this->source instanceof Announcement => $this->source->title ?? '運営からのお知らです',
            $this->source instanceof Meeting => '面談予約が確定しました',
            $this->source instanceof ChatMessage => '新着チャットメッセージがあります',
            $this->source instanceof QaReply => '質問掲示板に回答がありました',
            default => '新しいお知らせがあります',
        };
    }

    /**
     * 本文の生成
     */
    private function buildBody(): string
    {

        if ($this->source instanceof Announcement) {
            // お知らせの本文または抜粋を表示（50文字で切る例）
            return Str::limit($this->source->body, 50, '...');
        }

        if ($this->source instanceof Meeting) {
            $schedule = $this->source->scheduled_at?->format('Y/m/d H:i');

            return "次回の面談日時は {$schedule} です。";
        }

        if ($this->source instanceof ChatMessage) {
            $senderName = $this->source->sender->name ?? 'ユーザー';

            return "{$senderName}さんから新着メッセージが届きました。";
        }

        if ($this->source instanceof QaReply) {
            $title = $this->source->qaThread->title ?? '質問';

            return "「{$title}」に新しい回答がつきました。";
        }

        return '';
    }

    /**
     * 遷移先URLの生成
     */
    private function buildUrl(): string
    {
        if ($this->source instanceof Announcement) {
            // 外部の業務画面を持たないため空文字（または route('notifications.index')）
            return '';
        }

        if ($this->source instanceof Meeting) {
            return route('meetings.show', ['meeting' => $this->source->id]);
        }

        if ($this->source instanceof ChatMessage) {
            return route('chat.show', ['room' => $this->source->chat_room_id]);
        }

        if ($this->source instanceof QaReply) {
            return route('qa-board.show', ['thread' => $this->source->qa_thread_id]);
        }

        return url('/');
    }

    public static function sendForChatMessage(ChatRoom $room, User $sender, ChatMessage $message): void
    {
        $student = $room->enrollment->user;

        if ($sender->id === $student->id) {
            // 送信者が受講生の場合：担当コーチへ通知
            $coaches = $room->enrollment->certification->coaches;
            NotificationFacade::send($coaches, new self($message));
        } else {
            // 送信者がコーチ/管理者の場合：受講生へ通知
            $student->notify(new self($message));
        }
    }
}
