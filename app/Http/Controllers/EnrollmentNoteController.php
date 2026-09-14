<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreEnrollmentNoteRequest;
use App\Http\Requests\UpdateEnrollmentNoteRequest;
use App\Models\Enrollment;
use App\Models\EnrollmentNote;

class EnrollmentNoteController extends Controller
{
    public function store(StoreEnrollmentNoteRequest $request, Enrollment $enrollment)
    {
        $validated = $request->validated();
        $validated['author_id'] = $request->user()->id;

        $enrollment->notes()->create($validated);

        return back()->with('success', 'メモを作成しました');
    }

    public function edit(EnrollmentNote $note)
    {
        $this->authorize('update', $note);

        return view('enrollment-note.edit', compact('note'));
    }

    public function update(UpdateEnrollmentNoteRequest $request, EnrollmentNote $note)
    {
        $this->authorize('update', $note);

        $validated = $request->validated();
        $note->update($validated);
        $enrollment = $note->enrollment;

        return redirect()->route('enrollments.show', $enrollment)->with('success', '内容を更新しました');
    }

    public function destroy(EnrollmentNote $note)
    {
        $this->authorize('delete', $note);

        $note->delete();

        return back()->with('success', 'メモを削除しました');
    }
}
