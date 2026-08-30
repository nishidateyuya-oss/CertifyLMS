<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreQaReplyRequest;
use App\Models\QaReply;
use App\Models\QaThread;

class QaReplyController extends Controller
{
    public function store(StoreQaReplyRequest $request, QaThread $thread)
    {
        $validated = $request->validated();
        $validated['qa_thread_id'] = $thread->id;
        $request->user()->qaReplies()->create($validated);

        return back()->with('success', '回答を投稿しました');
    }

    public function edit(QaThread $thread, QaReply $reply)
    {
        $this->authorize('update', $reply);

        return view('qa-thread.reply-edit', compact('thread', 'reply'));
    }

    public function update(StoreQaReplyRequest $request, QaThread $thread, QaReply $reply)
    {
        $this->authorize('update', $reply);
        $validated = $request->validated();
        $reply->update($validated);

        return redirect()->route('qa-board.show', $thread)->with('success', '回答を更新しました');
    }

    public function delete(QaThread $thread, QaReply $reply)
    {
        $this->authorize('delete', $reply);
        $reply->delete();

        return back()->with('success', '回答を削除しました');
    }
}
