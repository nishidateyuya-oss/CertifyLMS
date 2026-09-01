<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PlanStatus;
use App\Http\Requests\StorePlanRequest;
use App\Models\Plan;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    public function index(Request $request)
    {
        $keyword = $request->input('keyword');
        $status = $request->input('status');

        $plans = Plan::query()
            ->when($request->keyword, function ($query, $keyword) {
                $query->where('name', 'like', '%'.$keyword.'%');
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })->paginate(7);

        return view('plan.management.index', compact('plans', 'keyword', 'status'));
    }

    public function create()
    {
        return view('plan.management.create');
    }

    public function store(StorePlanRequest $request)
    {
        $validated = $request->validated();
        $validated['status'] = PlanStatus::Draft;
        $validated['created_by_user_id'] = $request->user()->id;
        $validated['updated_by_user_id'] = $request->user()->id;
        Plan::create($validated);

        return redirect()->route('admin.plans.index')->with('success', '面談プランを作成しました');
    }

    public function show(Plan $plan)
    {
        return view('plan.management.show', [
            'plan' => $plan,
        ]);
    }

    public function edit(Plan $plan)
    {
        $this->authorize('update', $plan);

        return view('plan.management.edit', [
            'plan' => $plan,
        ]);
    }

    public function update(StorePlanRequest $request, Plan $plan)
    {
        $this->authorize('update', $plan);

        $validated = $request->validated();
        $validated['updated_by_user_id'] = $request->user()->id;
        $plan->update($validated);

        return redirect()->route('admin.plans.show', $plan)->with('success', '面談プランを更新しました');
    }

    public function destroy(Plan $plan)
    {
        $this->authorize('delete', $plan);

        if ($plan->status !== PlanStatus::Draft) {
            return back()->with('error', '下書き以外のプランは削除できません');
        }

        if ($plan->users()->exists()) {
            return back()->with('error', '受講者が登録されているプランは削除できません');
        }

        $plan->delete();

        return redirect()
            ->route('admin.plans.index')
            ->with('success', '面談プランを削除しました');
    }

    public function publish(Plan $plan)
    {
        $plan->update(['status' => PlanStatus::Published]);

        return back();
    }

    public function archive(Plan $plan)
    {

        $plan->update(['status' => PlanStatus::Archived]);

        return back();
    }

    public function unarchive(Plan $plan)
    {
        $plan->update(['status' => PlanStatus::Draft]);

        return back();
    }
}
