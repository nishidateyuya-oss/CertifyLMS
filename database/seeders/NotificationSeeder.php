<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Meeting;
use App\Models\ChatMessage;
use App\Models\QaReply;
use App\Notifications\NewMessageNotification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Mail;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        // テスト用のメール送信をスキップ（フェイク化）
        Mail::fake();

        $student = User::where('role', UserRole::Student)->first() ?? User::factory()->create(['role' => UserRole::Student]);
        $coach   = User::where('role', UserRole::Coach)->first() ?? User::factory()->create(['role' => UserRole::Coach]);

        // 1. 受講生（Student）向けの通知データを作成
        $this->seedStudentNotifications($student);

        // 2. コーチ（Coach）向けの通知データを作成
        $this->seedCoachNotifications($coach);
    }

    /**
     * 受講生宛ての通知シーディング
     */
    private function seedStudentNotifications(User $student): void
    {
        // ソースとなる各モデルを取得（無ければ Factory で作成）
        $meeting     = Meeting::first() ?? Meeting::factory()->create();
        $chatMessage = ChatMessage::first() ?? ChatMessage::factory()->create();
        $qaReply     = QaReply::first() ?? QaReply::factory()->create();

        $sources = [$meeting, $chatMessage, $qaReply];

        foreach ($sources as $index => $source) {
            // NewMessageNotification のコンストラクタに直接モデルインスタンスを渡す
            $notification = new NewMessageNotification($source);
            $student->notify($notification);

            // 奇数番目の通知だけテスト用に既読化
            if ($index % 2 === 1) {
                $student->unreadNotifications()->first()?->markAsRead();
            }
        }
    }

    /**
     * コーチ宛ての通知シーディング
     */
    private function seedCoachNotifications(User $coach): void
    {
        // コーチ側でも動かすため別レコードまたは最新のモデルを取得
        $meeting = Meeting::first() ?? Meeting::factory()->create();
        
        $notification = new NewMessageNotification($meeting);
        $coach->notify($notification);
    }
}
