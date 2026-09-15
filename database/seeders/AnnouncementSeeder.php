<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AnnouncementTargetType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Announcement;
use App\Models\Certification;
use App\Models\User;
use App\Notifications\NewMessageNotification;
use Illuminate\Database\Seeder;

class AnnouncementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. 前提データの準備
        $admin = User::where('role', 'admin')->first() ?? User::factory()->create(['role' => 'admin']);
        $certifications = Certification::all();
        $targetCertification = $certifications->first() ?? Certification::factory()->create();

        // 受講生ユーザーの取得（ロールが student のユーザー）
        $students = User::where('role', UserRole::Student)->where('status', UserStatus::InProgress)->get();
        if ($students->isEmpty()) {
            $students = User::factory()->count(5)->create([
                'role' => UserRole::Student,
                'status' => UserStatus::InProgress,
            ]);
        }

        $singleStudent = $students->first();

        // ------------------------------------------------------------------
        // パターン 1: 全受講生向けお知らせ (all_students)
        // ------------------------------------------------------------------
        $announcementAll = Announcement::create([
            'sender_id' => $admin->id,
            'target_type' => AnnouncementTargetType::AllStudents,
            'target_certification_id' => null,
            'target_user_id' => null,
            'title' => '【全体連絡】システムメンテナンスに伴うサービス一時停止のお知らせ',
            'body' => "受講生の皆様\n\nいつもご利用いただきありがとうございます。\n下記日程にてシステムメンテナンスを実施いたします。\n\n日時: 2026年9月20日 01:00 〜 05:00\n\nメンテナンス中はマイページへのアクセスができませんのでご注意ください。",
            'dispatched_count' => $students->count(),
            'dispatched_at' => now()->subDays(3),
            'created_at' => now()->subDays(3),
            'updated_at' => now()->subDays(3),
        ]);

        // 全受講生に NewMessageNotification を使って通知を発火
        foreach ($students as $student) {
            $student->notify(new NewMessageNotification($announcementAll));
        }

        // ------------------------------------------------------------------
        // パターン 2: 資格指定お知らせ (certification)
        // ------------------------------------------------------------------
        $certStudents = $students->take(3);

        $announcementCert = Announcement::create([
            'sender_id' => $admin->id,
            'target_type' => AnnouncementTargetType::Certification,
            'target_certification_id' => $targetCertification->id,
            'target_user_id' => null,
            'title' => "【{$targetCertification->name}】教材改訂および試験範囲の更新について",
            'body' => "対象資格をご受講中の皆様\n\n来月より「{$targetCertification->name}」のカリキュラム一部改訂が行われます。\n変更点の詳細は講義資料の「補足データ」をご確認ください。",
            'dispatched_count' => $certStudents->count(),
            'dispatched_at' => now()->subDays(1),
            'created_at' => now()->subDays(1),
            'updated_at' => now()->subDays(1),
        ]);

        // 対象資格の受講生のみに NewMessageNotification で通知
        foreach ($certStudents as $student) {
            $student->notify(new NewMessageNotification($announcementCert));
        }

        // ------------------------------------------------------------------
        // パターン 3: ユーザー指定お知らせ (user)
        // ------------------------------------------------------------------
        $announcementUser = Announcement::create([
            'sender_id' => $admin->id,
            'target_type' => AnnouncementTargetType::User,
            'target_certification_id' => null,
            'target_user_id' => $singleStudent->id,
            'title' => '【個別に連絡】学習進捗および面談のお申込みについて',
            'body' => "{$singleStudent->name} 様\n\n運営事務局です。\n学習の進捗状況はいかがでしょうか？\n現在、無料のキャリアカウンセリング枠に空きがございますので、ご希望の場合は面談予約画面よりお申し込みください。",
            'dispatched_count' => 1,
            'dispatched_at' => now()->subHours(5),
            'created_at' => now()->subHours(5),
            'updated_at' => now()->subHours(5),
        ]);

        // 指定の受講生1名のみに NewMessageNotification で通知
        $singleStudent->notify(new NewMessageNotification($announcementUser));
    }
}
