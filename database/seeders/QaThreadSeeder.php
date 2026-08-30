<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\QaThreadStatus;
use App\Models\Certification;
use App\Models\QaReply;
use App\Models\QaThread;
use App\Models\User;
use Illuminate\Database\Seeder;

class QaThreadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. 公開済みの資格を取得
        $certifications = Certification::where('status', 'published')
            ->whereNotNull('published_at')
            ->get();

        if ($certifications->isEmpty()) {
            $certifications = Certification::all();
        }

        if ($certifications->isEmpty()) {
            return;
        }

        // 2. 投稿者となる受講生を取得
        $students = User::where('role', 'student')->get();

        if ($students->isEmpty()) {
            return;
        }

        // 「自分の質問」動作確認用の固定受講生 (最初の受講生)
        $targetStudent = $students->first();

        // 3. 固定受講生によるスレッド（未解決・解決済のペア）を作成
        foreach ($certifications as $certification) {
            // ① 未解決スレッド (自分の質問)
            QaThread::create([
                'user_id' => $targetStudent->id,
                'certification_id' => $certification->id,
                'title' => '【質問】'.$certification->name.' の学習方法について',
                'body' => 'こちらの資格の効率的な勉強手順やおすすめの参照ドキュメントがあれば教えてほしいです。',
                'status' => QaThreadStatus::Unresolved, // または 'open' / '未解決'
                'created_at' => now()->subDays(rand(1, 5)),
                'updated_at' => now()->subDays(rand(1, 5)),
            ]);

            // ② 解決済スレッド (自分の質問 + 回答付き)
            $resolvedThread = QaThread::create([
                'user_id' => $targetStudent->id,
                'certification_id' => $certification->id,
                'title' => '【解決済】'.$certification->name.' 模擬試験の判定基準',
                'body' => '合格ラインの得点率について教えてください。',
                'status' => QaThreadStatus::Resolved, // または 'resolved' / '解決済'
                'created_at' => now()->subDays(rand(10, 20)),
                'updated_at' => now()->subDays(rand(1, 9)),
            ]);

            // 解決済スレッド用の回答を作成
            QaReply::create([
                'user_id' => $students->where('id', '!=', $targetStudent->id)->first()?->id ?? $targetStudent->id,
                'qa_thread_id' => $resolvedThread->id,
                'body' => '模擬試験では80%以上の正答率を目安に復習を進めるのがおすすめです。',
                'created_at' => $resolvedThread->created_at->addHours(2),
            ]);
        }

        // 4. ランダムな受講生によるスレッド（未解決・解決済を混在）を作成
        foreach ($certifications as $certification) {
            for ($i = 1; $i <= rand(5, 8); $i++) {
                $randomStudent = $students->random();

                // 未解決 と 解決済 をランダムに割り振る
                $status = rand(0, 1) === 1 ? QaThreadStatus::Resolved : QaThreadStatus::Unresolved;
                $createdAt = now()->subDays(rand(1, 30))->subHours(rand(1, 23));

                $thread = QaThread::create([
                    'user_id' => $randomStudent->id,
                    'certification_id' => $certification->id,
                    'title' => "{$certification->name} に関する質問 No.{$i}",
                    'body' => "質問の本文テキストです。詳細なエラー内容や疑問点が入ります。(サンプルID: {$i})",
                    'status' => $status,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                // 回答数（0件〜3件）をランダムに作成
                $replyCount = rand(0, 3);
                for ($r = 0; $r < $replyCount; $r++) {
                    QaReply::create([
                        'user_id' => $students->random()->id,
                        'qa_thread_id' => $thread->id,
                        'body' => "これは {$r} 件目の回答サンプルです。",
                        'created_at' => $createdAt->copy()->addHours(rand(1, 12)),
                    ]);
                }
            }
        }
    }
}
