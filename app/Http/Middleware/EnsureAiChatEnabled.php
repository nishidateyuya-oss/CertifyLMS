<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAiChatEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. 機能全体の有効化スイッチ
        if (! config('services.gemini.enabled')) {
            return $request->expectsJson()
                ? response()->json(['message' => 'AI相談機能は現在無効化されています。'], 530)
                : abort(404);
        }

        // 3. API Key の未設定設定チェック
        if (empty(config('services.gemini.api_key'))) {
            return response()->json([
                'message' => '現在AIサービスが準備中（API Key未設定）のため利用できません。',
            ], 503, [], JSON_UNESCAPED_UNICODE);
        }

        return $next($request);
    }
}
