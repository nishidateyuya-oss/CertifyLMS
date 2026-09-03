<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\ChatMessage;
use App\Models\Meeting;
use App\Models\QaReply;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

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
        return [
            'title'   => $this->buildTitle(),
            'message' => $this->buildBody(),
            'url'     => $this->buildUrl(),
        ];
    }

    /**
     * タイトルの生成 (instanceof を使って型判定)
     */
    private function buildTitle(): string
    {
        return match (true) {
            $this->source instanceof Meeting     => '面談予約が確定しました',
            $this->source instanceof ChatMessage => '新着チャットメッセージがあります',
            $this->source instanceof QaReply     => '質問掲示板に回答がありました',
            default                              => '新しいお知らせがあります',
        };
    }

    /**
     * 本文の生成
     */
    private function buildBody(): string
    {
        if ($this->source instanceof Meeting) {
            $schedule = $this->source->scheduled_at;
            return "次回の面談は {$schedule} です。";
        }

        if ($this->source instanceof ChatMessage) {
            return '担当コーチからメッセージが届きました。';
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

}
