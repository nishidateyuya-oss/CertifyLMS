<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AiChatMessageRole;
use App\Enums\AiChatMessageStatus;
use App\Models\AiChatConversation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    private string $apiKey;

    private string $model;

    public function __construct()
    {
        $this->apiKey = (string) config('services.gemini.api_key');
        $this->model = (string) config('services.gemini.model', 'gemini-1.5-flash');
    }

    /**
     * 対話メッセージを生成
     */
    public function generateResponse(AiChatConversation $conversation, string $newUserPrompt): array
    {
        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

        // 1. システムプロンプト（教材文脈・資格の付与）
        $systemInstruction = $this->buildSystemInstruction($conversation);

        // 2. 直近の会話履歴（Completed のメッセージのみ取得）
        $contents = [];
        $history = $conversation->messages()
            ->where('status', AiChatMessageStatus::Completed)
            ->latest()
            ->take(10)
            ->get()
            ->reverse();

        foreach ($history as $msg) {
            // Gemini API の仕様に合わせて role を user / model に変換
            $role = $msg->role === AiChatMessageRole::User ? 'user' : 'model';

            $contents[] = [
                'role' => $role,
                'parts' => [['text' => $msg->content]],
            ];
        }

        // 今回の入力メッセージを末尾に追加
        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $newUserPrompt]],
        ];

        $payload = [
            'systemInstruction' => [
                'parts' => [['text' => $systemInstruction]],
            ],
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 1000,
            ],
        ];

        $startTime = microtime(true);

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'x-goog-api-key' => $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post($endpoint, $payload);
            $responseTimeMs = (int) round((microtime(true) - $startTime) * 1000);

            if ($response->successful()) {
                $data = $response->json();
                $replyText = $data['candidates'][0]['content']['parts'][0]['text'] ?? '回答を取得できませんでした。';
                $usage = $data['usageMetadata'] ?? [];

                return [
                    'success' => true,
                    'content' => $replyText,
                    'meta' => [
                        'model_name' => $this->model,
                        'prompt_tokens' => $usage['promptTokenCount'] ?? null,
                        'candidates_tokens' => $usage['candidatesTokenCount'] ?? null,
                        'total_tokens' => $usage['totalTokenCount'] ?? null,
                        'response_time_ms' => $responseTimeMs,
                    ],
                ];
            }

            Log::error('Gemini API Error Response', ['status' => $response->status(), 'body' => $response->body()]);

            return [
                'success' => false,
                'content' => 'AIの応答取得に失敗しました。時間をおいて再度送信してください。',
                'error' => "HTTP {$response->status()}: {$response->body()}",
                'meta' => ['model_name' => $this->model, 'response_time_ms' => $responseTimeMs],
            ];
        } catch (\Exception $e) {
            Log::error('Gemini API Connection Exception: '.$e->getMessage());

            return [
                'success' => false,
                'content' => '通信エラーが発生しました。接続状況を確認の上、再試行してください。',
                'error' => $e->getMessage(),
                'meta' => ['model_name' => $this->model, 'response_time_ms' => null],
            ];
        }
    }

    /**
     * 初回会話のタイトル自動生成
     */
    public function generateTitle(string $firstPrompt): string
    {
        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [[
                        'text' => "以下の質問内容の簡潔なタイトル（20文字以内、記号不使用）を作成してください。\n質問: {$firstPrompt}",
                    ]],
                ],
            ],
        ];

        try {
            $res = Http::timeout(10)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post($endpoint, $payload);
            if ($res->successful()) {
                $title = trim($res->json('candidates.0.content.parts.0.text') ?? '');

                return mb_substr($title, 0, 20) ?: '新しい相談';
            }
        } catch (\Exception $e) {
            // タイトル生成エラー時はフォールバック
        }

        return '新しい相談';
    }

    /**
     * 資格・教材文脈に応じたコンテキストプロンプトの組み立て
     */
    private function buildSystemInstruction(AiChatConversation $conversation): string
    {
        $prompt = "あなたは受講生の学習をサポートする頼れるAIアシスタントです。親切丁寧かつ端的に回答してください。\n";

        if ($conversation->target_certification) {
            $prompt .= "【目標資格】: {$conversation->target_certification}\n";
        }

        if ($conversation->section_id && $conversation->relationLoaded('section')) {
            $section = $conversation->section;
            $prompt .= "【現在閲覧中の教材Section】: {$section->title}\n";
            $prompt .= '【Section概要】: '.mb_substr($section->content_summary ?? '', 0, 300)."\n";
        }

        return $prompt;
    }
}
