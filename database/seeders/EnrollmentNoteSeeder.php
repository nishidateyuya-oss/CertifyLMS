<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Enrollment;
use App\Models\EnrollmentNote;
use App\Models\User;
use Illuminate\Database\Seeder;

class EnrollmentNoteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. 各ロールのユーザーを取得
        $admin = User::where('role', UserRole::Admin)->first() 
            ?? User::factory()->create(['role' => UserRole::Admin]);

        $coaches = User::where('role', UserRole::Coach)->get();
        if ($coaches->count() < 2) {
            $coaches = User::factory()->count(2)->create(['role' => UserRole::Coach]);
        }

        $students = User::where('role', UserRole::Student)->get();
        if ($students->count() < 2) {
            $students = User::factory()->count(2)->create(['role' => UserRole::Student]);
        }

        $primaryCoach = $coaches->first();
        $otherCoach = $coaches->last();
        $studentA = $students->first();
        $studentB = $students->last();

        // 2. 受講登録（Enrollment）の準備
        // 担当資格（例: studentA の受講登録。担当コーチ = primaryCoach と想定）
        $assignedEnrollment = Enrollment::firstOrCreate([
            'user_id' => $studentA->id,
            // 必要に応じて担当コーチ等のカラムを指定してください（例: 'coach_id' => $primaryCoach->id）
        ]);

        // 担当外資格（例: studentB の受講登録。担当コーチ = otherCoach と想定）
        $unassignedEnrollment = Enrollment::firstOrCreate([
            'user_id' => $studentB->id,
            // 'coach_id' => $otherCoach->id
        ]);

        // ----------------------------------------------------
        // 3. テスト要件を満たすメモデータの作成
        // ----------------------------------------------------

        // A. 担当資格（studentA）に対するメモ群
        // A-1. 担当コーチ（primaryCoach）自身が作成したメモ（編集・削除可能の確認用）
        EnrollmentNote::create([
            'enrollment_id' => $assignedEnrollment->id,
            'author_id' => $primaryCoach->id,
            'body' => '【担当コーチ記載】初回カウンセリングを実施しました。目標設定完了。',
        ]);

        // A-2. 別のコーチ（otherCoach）が作成したメモ（閲覧・他者編集不可の確認用）
        EnrollmentNote::create([
            'enrollment_id' => $assignedEnrollment->id,
            'author_id' => $otherCoach->id,
            'body' => '【他コーチ記載】代理面談を実施。進捗良好です。',
        ]);

        // A-3. 管理者（admin）が作成したメモ（管理者の越境・権限確認用）
        EnrollmentNote::create([
            'enrollment_id' => $assignedEnrollment->id,
            'author_id' => $admin->id,
            'body' => '【管理者記載】受講期間の延長リクエストを承認しました。',
        ]);

        // B. 担当外資格（studentB）に対するメモ群
        // B-1. 担当外の受講登録にメモが存在する状態（primaryCoach からの閲覧拒否テスト用）
        EnrollmentNote::create([
            'enrollment_id' => $unassignedEnrollment->id,
            'author_id' => $otherCoach->id,
            'body' => '【担当外資格メモ】担当コーチによる学習アドバイス。',
        ]);

        // B-2. 受講生本人（studentB）への非公開確認用（受講生画面でメモが秘匿されているかの検証用）
        EnrollmentNote::create([
            'enrollment_id' => $unassignedEnrollment->id,
            'author_id' => $admin->id,
            'body' => '【管理者記載】内部連絡事項（受講生非公開対象）。',
        ]);
    }
}
