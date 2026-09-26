<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\GoogleCredential;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GoogleCalendarController extends Controller
{
    /**
     * Google 認可画面へのリダイレクト
     */
    public function connect(Request $request)
    {
        $state = Str::random(40);
        $request->session()->put('google_oauth_state', $state);

        $query = http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => route('settings.google-calendar.callback'),
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/calendar.events https://www.googleapis.com/auth/calendar.freebusy',
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state,
        ]);

        return redirect('https://accounts.google.com/o/oauth2/v2/auth?'.$query);
    }

    /**
     * Google からのコールバック処理
     */
    public function callback(Request $request)
    {
        $sessionState = $request->session()->pull('google_oauth_state');

        // CSRF / なりすまし防止のための State 検証
        if (empty($sessionState) || $request->input('state') !== $sessionState) {
            abort(403, '不正なリクエスト（State検証エラー）です。');
        }

        if ($request->has('error')) {
            return redirect()->route('settings.availability.index')
                ->with('error', 'Google アカウントの連携がキャンセルされました。');
        }

        // 認可コードをトークンに引き換え
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'code' => $request->input('code'),
            'grant_type' => 'authorization_code',
            'redirect_uri' => route('settings.google-calendar.callback'),
        ]);

        if ($response->failed()) {
            Log::error('Google OAuth Callback Token Error: '.$response->body());

            return redirect()->route('settings.availability.index')
                ->with('error', 'トークンの取得に失敗しました。');
        }

        $data = $response->json();

        // ログイン中コーチのアカウントに保存
        GoogleCredential::updateOrCreate(
            ['user_id' => auth()->id()],
            [
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'] ?? null, // 既存のト－クンがある場合上書き注意
                'token_expires_at' => now()->addSeconds($data['expires_in']),
                'connected_at' => now(),
            ]
        );

        return redirect()->route('settings.availability.index')
            ->with('success', 'Google カレンダーと連携しました。');
    }

    /**
     * Google カレンダー連携の解除
     */
    public function disconnect()
    {
        $account = GoogleCredential::where('user_id', auth()->id())->first();

        if ($account) {
            // Google API でトークン失効（任意・失敗しても無視してDB削除を優先）
            if ($account->access_token) {
                try {
                    Http::asForm()->post('https://oauth2.googleapis.com/revoke', [
                        'token' => $account->access_token,
                    ]);
                } catch (\Exception $e) {
                    Log::info('Token revoke failed: '.$e->getMessage());
                }
            }

            $account->delete();
        }

        return redirect()->route('settings.availability.index')
            ->with('success', 'Google カレンダーとの連携を解除しました。');
    }
}
