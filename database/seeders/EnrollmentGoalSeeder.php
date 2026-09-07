<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Enrollment;
use App\Models\EnrollmentGoal;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class EnrollmentGoalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. 固定受講生（画面表示・UI挙動確認用）の目標を生成
        $fixedStudent = User::where('role', 'student')
            ->where('email', 'student@example.com') // 開発用固定メールアドレス指定（プロジェクトの定義に合わせて調整してください）
            ->first() ?? User::where('role', 'student')->first();

        if ($fixedStudent) {
            $this->seedFixedStudentGoals($fixedStudent);
        }

        // 2. その他のデモ受講生（コーチ・管理者・他受講生の閲覧認可チェック用）の目標を散らして生成
        $demoStudents = User::where('role', 'student')
            ->when($fixedStudent, fn ($query) => $query->where('id', '!=', $fixedStudent->id))
            ->get();

        foreach ($demoStudents as $student) {
            $this->seedDemoStudentGoals($student);
        }
    }

    /**
     * 固定受講生の目標データ（達成マーク・解除・編集・削除のUI検証用）
     */
    private function seedFixedStudentGoals(User $student): void
    {
        // 生徒に関連づく受講登録（Enrollment）を取得または作成
        $enrollments = Enrollment::where('user_id', $student->id)->get();

        if ($enrollments->isEmpty()) {
            return;
        }

        foreach ($enrollments as $enrollment) {
            // 未達成目標（編集・削除・達成マークの操作確認用）
            EnrollmentGoal::create([
                'user_id' => $student->id,
                'enrollment_id' => $enrollment->id,
                'title' => '毎週10時間以上の学習時間を確保する',
                'target_date' => Carbon::now()->addMonth()->format('Y-m-d'),
                'description' => '平日2時間、週末3時間ずつのペースで進める。',
                'achieved_at' => null,
            ]);

            EnrollmentGoal::create([
                'user_id' => $student->id,
                'enrollment_id' => $enrollment->id,
                'title' => '中間課題を提出する',
                'target_date' => Carbon::now()->addDays(14)->format('Y-m-d'),
                'description' => '提出期限までにコーチの事前レビューを受けること。',
                'achieved_at' => null,
            ]);

            // 達成済目標（視覚的な達成マーク・解除操作の確認用）
            EnrollmentGoal::create([
                'user_id' => $student->id,
                'enrollment_id' => $enrollment->id,
                'title' => '第1章の環境構築を完了させる',
                'target_date' => Carbon::now()->subDays(7)->format('Y-m-d'),
                'description' => 'ローカル開発環境のセットアップと動作確認。',
                'achieved_at' => Carbon::now()->subDays(5)->format('Y-m-d'),
            ]);
        }
    }

    /**
     * デモ受講生の目標データ（コーチ/管理者/他生徒からのアクセス認可分岐検証用）
     */
    private function seedDemoStudentGoals(User $student): void
    {
        $enrollments = Enrollment::where('user_id', $student->id)->get();

        foreach ($enrollments as $index => $enrollment) {
            // 奇数番目/偶数番目で達成・未達成のバリエーションを持たせる
            $isCompleted = $index % 2 === 0;

            EnrollmentGoal::create([
                'user_id' => $student->id,
                'enrollment_id' => $enrollment->id,
                'title' => "受講生（{$student->name}）の個人目標 - ".($isCompleted ? '達成済' : '進行中'),
                'target_date' => $isCompleted
                    ? Carbon::now()->subDays(rand(1, 15))->format('Y-m-d')
                    : Carbon::now()->addDays(rand(10, 30))->format('Y-m-d'),
                'description' => '権限確認用：コーチおよび管理者から閲覧可能か、他受講生から制限されるかテスト',
                'achieved_at' => $isCompleted ? Carbon::now()->subDays(rand(1, 5))->format('Y-m-d') : null,
            ]);
        }
    }
}
