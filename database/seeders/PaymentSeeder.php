<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\MeetingPack;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. 生徒（student）ロールのユーザーを取得（いなければ1人作成）
        $students = User::where('role', UserRole::Student)->get();

        if ($students->isEmpty()) {
            $students = collect([
                User::factory()->create([
                    'name' => 'テスト生徒',
                    'email' => 'student@example.com',
                    'role' => UserRole::Student,
                ]),
            ]);
        }

        // 2. 面談パックを取得（存在しない場合はスキップ）
        $meetingPacks = MeetingPack::all();

        if ($meetingPacks->isEmpty()) {
            $this->command->warn('MeetingPack が存在しないため、PaymentSeeder をスキップしました。');

            return;
        }

        // 3. 各生徒に対してランダムに決済履歴を生成
        foreach ($students as $student) {
            foreach ($meetingPacks->random(min(2, $meetingPacks->count())) as $pack) {

                // 成功（Succeeded）データの作成
                Payment::create([
                    'user_id' => $student->id,
                    'meeting_pack_id' => $pack->id,
                    'amount' => $pack->price,
                    'quantity' => $pack->meeting_count,
                    'status' => PaymentStatus::Succeeded,
                    'paid_at' => now()->subDays(rand(1, 30)),
                    'created_at' => now()->subDays(rand(1, 30)),
                ]);

                // 処理中（Pending）または失敗（Failed）データの作成（テスト用）
                Payment::create([
                    'user_id' => $student->id,
                    'meeting_pack_id' => $pack->id,
                    'amount' => $pack->price,
                    'quantity' => $pack->meeting_count,
                    'status' => PaymentStatus::Pending,
                    'paid_at' => null,
                    'created_at' => now()->subHours(rand(1, 12)),
                ]);
            }
        }
    }
}
