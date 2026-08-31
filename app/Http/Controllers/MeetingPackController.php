<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\MeetingPackStatus;
use App\Http\Requests\StoreMeetingPackRequest;
use App\Models\MeetingPack;
use Illuminate\Http\Request;

class MeetingPackController extends Controller
{
    public function index(Request $request)
    {
        $keyword = $request->input('keyword');
        $status = $request->input('status');

        $plans = MeetingPack::query()
            ->when($request->keyword, function ($query, $keyword) {
                $query->where('name', 'like', '%'.$keyword.'%');
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })->paginate(7);

        return view('meeting-pack.management.index', compact('plans', 'keyword', 'status'));
    }

    public function create()
    {
        return view('meeting-pack.management.create');
    }

    public function store(StoreMeetingPackRequest $request)
    {
        $validated = $request->validated();
        $validated['status'] = MeetingPackStatus::Draft;
        $validated['created_by_user_id'] = $request->user()->id;
        $validated['updated_by_user_id'] = $request->user()->id;
        MeetingPack::create($validated);

        return redirect()->route('admin.meeting-packs.index')->with('success', '面談パックを作成しました');
    }

    public function show(MeetingPack $meetingPack)
    {
        return view('meeting-pack.management.show', [
            'plan' => $meetingPack,
        ]);
    }

    public function edit(MeetingPack $meetingPack)
    {
        $this->authorize('update', $meetingPack);

        return view('meeting-pack.management.edit', [
            'plan' => $meetingPack,
        ]);
    }

    public function update(StoreMeetingPackRequest $request, MeetingPack $meetingPack)
    {
        $this->authorize('update', $meetingPack);

        $validated = $request->validated();
        $validated['updated_by_user_id'] = $request->user()->id;
        $meetingPack->update($validated);

        return redirect()->route('admin.meeting-packs.show', $meetingPack)->with('success', '面談パックを更新しました');
    }

    public function destroy(MeetingPack $meetingPack)
    {
        $this->authorize('delete', $meetingPack);

        if ($meetingPack->status === MeetingPackStatus::Published) {
            return back()->with('error', '公開中の面談パックは削除できません');
        }

        $meetingPack->delete();

        return redirect()->route('admin.meeting-packs.index')->with('success', '面談パックを削除しました');
    }

    public function publish(MeetingPack $plan)
    {
        $plan->update(['status' => MeetingPackStatus::Published]);

        return back();
    }

    public function archive(MeetingPack $plan)
    {
        $plan->update(['status' => MeetingPackStatus::Archived]);

        return back();
    }

    public function unarchive(MeetingPack $plan)
    {
        $plan->update(['status' => MeetingPackStatus::Draft]);

        return back();
    }
}
