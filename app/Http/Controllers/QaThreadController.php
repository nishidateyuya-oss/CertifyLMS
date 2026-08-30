<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\CertificationStatus;
use App\Enums\QaThreadStatus;
use App\Enums\UserRole;
use App\Http\Requests\StoreQaThreadRequest;
use App\Http\Requests\UpdateQaThreadRequest;
use App\Models\Certification;
use App\Models\QaThread;
use Illuminate\Http\Request;

class QaThreadController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(['keyword', 'certification_id', 'status']);

        // 2. クエリビルダで検索条件を動的に追加
        $threads = QaThread::query()
            // 【キーワード検索】タイトルまたは本文にキーワードが含まれるか
            ->when($request->filled('keyword'), function ($query) use ($request) {
                $keyword = '%'.$request->input('keyword').'%';
                $query->where(function ($q) use ($keyword) {
                    $q->where('title', 'like', $keyword)
                        ->orWhere('body', 'like', $keyword);
                });
            })
            // 【資格絞り込み】選択されている場合のみ一致条件を追加
            ->when($request->filled('certification_id'), function ($query) use ($request) {
                $query->where('certification_id', $request->input('certification_id'));
            })
            // 【ステータス絞り込み】選択されている場合のみ一致条件を追加
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->input('status'));
            })
            ->latest() // 新しい順に並び替え
            ->paginate(15) // ページネーションに送信パラメータを保持
            ->withQueryString(); // ← ページ切り替え時にも検索条件（URLパラメータ）を維持する重要な処理

        if ($request->user()->role === UserRole::Admin) {
            $publishedStatus = CertificationStatus::Published;
            $certifications = Certification::all();

            // 4. ビューへ受け渡し
            return view('qa-thread.index', compact(
                'threads',
                'filters',
                'certifications',
                'publishedStatus'
            ));
        }

        // 3. フォームで使うマスタデータなどを取得
        $publishedStatus = CertificationStatus::Published;
        $certifications = Certification::where('status', $publishedStatus)->get();

        // 4. ビューへ受け渡し
        return view('qa-thread.index', compact(
            'threads',
            'filters',
            'certifications',
            'publishedStatus'
        ));
    }

    public function show(QaThread $thread)
    {
        return view('qa-thread.show', compact('thread'));
    }

    public function create()
    {
        $certifications = Certification::where('status', CertificationStatus::Published);

        return view('qa-thread.create', compact('certifications'));
    }

    public function store(StoreQaThreadRequest $request)
    {
        $validated = $request->validated();
        $validated['status'] = QaThreadStatus::Unresolved;
        auth()->user()->qaThreads()->create($validated);

        return redirect()->route('qa-board.index')->with('success', '質問を投稿しました');
    }

    public function edit(QaThread $thread)
    {
        $this->authorize('update', $thread);

        return view('qa-thread.edit', compact('thread'));
    }

    public function update(UpdateQaThreadRequest $request, QaThread $thread)
    {
        $this->authorize('update', $thread);
        $validated = $request->validated();
        $thread->update($validated);

        return redirect()->route('qa-board.show', $thread)->with('success', '質問を更新しました');
    }

    public function delete(QaThread $thread)
    {
        $this->authorize('delete', $thread);
        $thread->delete();

        if (auth()->user()->role === UserRole::Admin) {
            return redirect()->route('admin.qa-board.index')->with('success', '質問を削除しました');
        }

        return redirect()->route('qa-board.index')->with('success', '質問を削除しました');
    }

    public function resolve(QaThread $thread)
    {
        $thread->update(['status' => QaThreadStatus::Resolved]);

        return back();
    }

    public function unresolve(QaThread $thread)
    {
        $thread->update(['status' => QaThreadStatus::Unresolved]);

        return back();
    }
}
