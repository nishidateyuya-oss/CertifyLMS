<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\GoogleCredential;
use App\Models\Meeting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleCalendarService
{
    /**
     * 有効なアクセストークンを取得（期限切れ時は自動リフレッシュ）
     */
    public function getValidAccessToken(GoogleCredential $account): ?string
    {
        // 期限切れチェック（余裕を持たせて5分前に更新）
        if ($account->token_expires_at && $account->token_expires_at->subMinutes(5)->isPast()) {
            return $this->refreshAccessToken($account);
        }

        return $account->access_token;
    }

    /**
     * Refresh Token を使用してアクセストークンを再発行
     */
    protected function refreshAccessToken(GoogleCredential $account): ?string
    {
        if (! $account->refresh_token) {
            Log::warning("Google Refresh Token is missing for user ID: {$account->user_id}");

            return null;
        }

        try {
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'refresh_token' => $account->refresh_token,
                'grant_type' => 'refresh_token',
            ]);

            if ($response->failed()) {
                Log::error('Failed to refresh Google token: '.$response->body());

                return null;
            }

            $data = $response->json();
            $account->update([
                'access_token' => $data['access_token'],
                'token_expires_at' => now()->addSeconds($data['expires_in']),
            ]);

            return $data['access_token'];
        } catch (\Exception $e) {
            Log::error('Google Token Refresh Error: '.$e->getMessage());

            return null;
        }
    }

    /**
     * 指定期間の Google カレンダー予定 (Busy スロット) を取得
     * ※ エラー時は空配列を返し、既存判定を止めない (フォールバック)
     */
    public function getBusySlots(GoogleCredential $account, Carbon $start, Carbon $end): array
    {
        try {
            $token = $this->getValidAccessToken($account);
            if (! $token) {
                return [];
            }

            $response = Http::withToken($token)
                ->post('https://www.googleapis.com/calendar/v3/freeBusy', [
                    'timeMin' => $start->toIso8601String(),
                    'timeMax' => $end->toIso8601String(),
                    'items' => [['id' => 'primary']],
                ]);

            if ($response->failed()) {
                Log::warning('Google FreeBusy API failed: '.$response->body());

                return [];
            }

            $busyData = $response->json('calendars.primary.busy', []);

            return array_map(function ($slot) {
                return [
                    'start' => Carbon::parse($slot['start']),
                    'end' => Carbon::parse($slot['end']),
                ];
            }, $busyData);
        } catch (\Exception $e) {
            Log::warning('Google Calendar FreeBusy Exception: '.$e->getMessage());

            return [];
        }
    }

    /**
     * 面談成立時に Google カレンダーへイベント登録
     * ※ エラー時は null を返し、LMS 側の予約を正常完了させる (フォールバック)
     */
    public function createMeetingEvent(GoogleCredential $account, Meeting $meeting): ?string
    {
        try {
            $token = $this->getValidAccessToken($account);
            if (! $token) {
                return null;
            }

            $coach = $meeting->coach;
            $student = $meeting->student;

            // 固定面談URL（プロフィール等から取得）
            $meetingUrl = $coach->meeting_url ?? '固定面談URL未設定';

            // 開始時間（scheduled_at）と 1 時間後の終了時間を作成
            $startAt = Carbon::parse($meeting->scheduled_at)->setTimezone('Asia/Tokyo');
            $endAt = $startAt->copy()->addHour();

            $response = Http::withToken($token)
                ->post('https://www.googleapis.com/calendar/v3/calendars/primary/events', [
                    'summary' => "【面談】{$student->name} 様",
                    'description' => "受講生: {$student->name}\n面談URL: {$meetingUrl}\n\n※この予定はLMSから自動登録されています。",
                    'start' => [
                        'dateTime' => $startAt->toIso8601String(),
                    ],
                    'end' => [
                        'dateTime' => $endAt->toIso8601String(),
                    ],
                ]);

            if ($response->failed()) {
                Log::error('Failed to create Google Calendar event: '.$response->body());

                return null;
            }

            $eventData = $response->json();
            Log::info('Google Calendar Event Created Successfully!', [
                'event_id' => $eventData['id'] ?? null,
                'html_link' => $eventData['htmlLink'] ?? null,
            ]);

            return $eventData['id'] ?? null;
        } catch (\Throwable $e) {
            // \Throwable で Error も Exception も確実にログに出力する
            Log::error('Google Calendar Create Event Exception: '.$e->getMessage(), [
                'exception' => $e,
            ]);

            return null;
        }
    }

    /**
     * 面談キャンセル時に Google カレンダーからイベント削除
     * ※ エラー時でも LMS 側のキャンセル処理は打ち消さない (フォールバック)
     */
    public function deleteMeetingEvent(GoogleCredential $account, string $googleEventId): bool
    {
        try {
            $token = $this->getValidAccessToken($account);
            if (! $token) {
                return false;
            }

            $response = Http::withToken($token)
                ->delete("https://www.googleapis.com/calendar/v3/calendars/primary/events/{$googleEventId}");

            // 404 (既に手動削除されている場合) も含めて成功扱いとする
            if ($response->successful() || $response->status() === 404) {
                return true;
            }

            Log::error('Failed to delete Google Calendar event: '.$response->body());

            return false;
        } catch (\Exception $e) {
            Log::error('Google Calendar Delete Event Exception: '.$e->getMessage());

            return false;
        }
    }
}
