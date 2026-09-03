<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    // GET /notifications
    public function index(Request $request)
    {
        $user = $request->user();

        // 1. クエリパラメータから tab を取得（デフォルトは 'all'）
        $tab = $request->query('tab', 'all');

        // 2. タブの選択状態に合わせて通知を取得
        $query = $user->notifications();

        if ($tab === 'unread') {
            $query = $user->unreadNotifications();
        }

        $notifications = $query->paginate(15)->withQueryString();

        // 3. 未読件数を取得
        $unreadCount = $user->unreadNotifications()->count();

        // 4. compact に 'tab' を渡す
        return view('notifications.index', compact('notifications', 'unreadCount', 'tab'));
    }

    // POST /notifications/{notification}/read
    public function read(Request $request, DatabaseNotification $notification)
    {
        // 他人宛の通知は認可エラー（403）を返す
        if ($notification->notifiable_id !== $request->user()->id) {
            abort(403);
        }

        // 既読化処理
        if ($notification->unread()) {
            $notification->markAsRead();
        }

        // dataカラムに含まれる遷移先URLを取得してリダイレクト
        $redirectUrl = $notification->data['url'] ?? route('notifications.index');

        return redirect($redirectUrl);
    }

    // POST /notifications/read-all
    public function readAll(Request $request)
    {
        // 未読通知をまとめて既読化
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'すべての通知を既読にしました');
    }
}
