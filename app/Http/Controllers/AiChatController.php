<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\AiChatMessageRole;
use App\Enums\AiChatMessageStatus;
use App\Http\Requests\StoreConversationRequest;
use App\Http\Requests\StoreMessageRequest;
use App\Models\AiChatConversation;
use App\Models\AiChatMessage;
use App\Services\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AiChatController extends Controller
{
    public function __construct(private GeminiService $geminiService) {}

    /**
     * 会話一覧の取得（フル画面・ウィジェット共通）
     */
    public function index(Request $request)
    {
        return view('ai-chat.empty-state');
    }

    public function show(AiChatConversation $conversation, Request $request)
    {
        // 所有者チェック
        $this->authorizeOwner($conversation, $request->user()->id);

        // API リクエスト（JSON要求）の場合
        if ($request->expectsJson()) {
            $conversation->load(['messages' => fn ($q) => $q->orderBy('created_at', 'asc')]);

            return response()->json($conversation);
        }

        // 画面表示用：メッセージ履歴の Eager Loading
        $conversation->load(['messages' => fn ($q) => $q->orderBy('created_at', 'asc')]);

        // Blade テンプレートに $conversation を渡して描画
        return view('ai-chat.show', [
            'conversation' => $conversation,
        ]);
    }

    /**
     * 新規会話作成
     */
    public function store(StoreConversationRequest $request)
    {
        $user = $request->user();
        $validated = $request->validated();
        $userMessageText = $validated['message'] ?? null;

        // 1日あたりの制限チェック
        if ($userMessageText) {
            $todayCount = AiChatMessage::whereHas('conversation', fn ($q) => $q->where('user_id', $user->id))
                ->where('role', AiChatMessageRole::User)
                ->whereDate('created_at', today())
                ->count();

            if ($todayCount >= config('services.gemini.daily_limit', 50)) {
                return redirect()->back()->withErrors([
                    'message' => '1日のAI質問上限回数に達しました。明日再度お試しください。',
                ]);
            }
        }

        // 新規会話作成
        $conversation = DB::transaction(function () use ($user, $validated) {
            return $user->aiChatConversations()->create([
                'section_id' => $validated['section_id'] ?? null,
                'title' => '新しい相談',
            ]);
        });

        if ($userMessageText) {
            $this->sendMessage($conversation, $userMessageText);
        }

        if (! $request->expectsJson()) {
            return redirect()->route('ai-chat.conversations.show', $conversation);
        }

        return response()->json([
            'conversation_id' => $conversation->id,
            'title' => $conversation->title,
        ]);
    }

    /**
     * メッセージ追加送信
     */
    public function storeMessage(StoreMessageRequest $request, AiChatConversation $conversation)
    {
        $this->authorizeOwner($conversation, $request->user()->id);

        $user = $request->user();
        $todayCount = AiChatMessage::whereHas('conversation', fn ($q) => $q->where('user_id', $user->id))
            ->where('role', AiChatMessageRole::User)
            ->whereDate('created_at', today())
            ->count();

        if ($todayCount >= config('services.gemini.daily_limit', 50)) {
            return redirect()->back()->withErrors([
                'message' => '1日のAI質問上限回数に達しました。明日再度お試しください。',
            ]);
        }

        $this->sendMessage($conversation, $request->validated()['content']);

        if (! $request->expectsJson()) {
            return redirect()->route('ai-chat.conversations.show', $conversation);
        }

        return response()->json(['message' => '送信完了']);
    }

    /**
     * メッセージ送信・Gemini応答共通処理
     */
    private function sendMessage(AiChatConversation $conversation, string $userMessageText): void
    {
        // 1. ユーザー発言メッセージの保存（user_id を追加）
        $conversation->messages()->create([
            'user_id' => $conversation->user_id, // ← ここを追加
            'role' => AiChatMessageRole::User,
            'content' => $userMessageText,
            'status' => AiChatMessageStatus::Completed,
        ]);

        $conversation->load('section');

        \Log::info('Gemini送信テスト', [
            'userMessageText' => $userMessageText,
            'text_length' => mb_strlen($userMessageText),
        ]);

        // 2. Gemini API の実行
        $result = $this->geminiService->generateResponse($conversation, $userMessageText);

        // 3. AI応答メッセージの保存（user_id を追加）
        $isSuccess = $result['success'] ?? false;

        $conversation->messages()->create([
            'user_id' => $conversation->user_id, // ← ここを追加
            'role' => AiChatMessageRole::Assistant,
            'content' => $result['content'] ?? '',
            'status' => $isSuccess ? AiChatMessageStatus::Completed : AiChatMessageStatus::Error,
            'error_detail' => $isSuccess ? null : ($result['error'] ?? 'API error'),
            'response_time_ms' => $result['meta']['response_time_ms'] ?? null,
            'output_tokens' => $result['meta']['candidates_tokens'] ?? $result['meta']['output_tokens'] ?? null,
        ]);

        if ($conversation->auto_title_enabled && $conversation->messages()->count() <= 2) {
            $newTitle = $this->geminiService->generateTitle($userMessageText);
            $conversation->update(['title' => $newTitle]);
        }

        $conversation->touch();
    }

    /**
     * 所有者判定 Guard
     */
    private function authorizeOwner(AiChatConversation $conversation, string|int $userId): void
    {
        if ($conversation->user_id !== $userId) {
            abort(403, 'この会話へアクセスする権限がありません。');
        }
    }
}
