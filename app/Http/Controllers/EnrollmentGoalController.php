<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreEnrollGoalRequest;
use App\Http\Requests\UpdateEnrollGoalRequest;
use App\Models\Enrollment;
use App\Models\EnrollmentGoal;
use Illuminate\Support\Carbon;

class EnrollmentGoalController extends Controller
{
    public function store(StoreEnrollGoalRequest $request, Enrollment $enrollment)
    {
        $validated = $request->validated();
        $validated['enrollment_id'] = $enrollment->id;

        $request->user()->enrollmentGoals()->create($validated);

        return back()->with('success', '目標を作成しました');
    }

    public function edit(EnrollmentGoal $goal)
    {
        return view('enrollment-goal.edit', compact('goal'));
    }

    public function update(UpdateEnrollGoalRequest $request, EnrollmentGoal $goal)
    {

        $this->authorize('update', $goal);
        $validated = $request->validated();

        $goal->update($validated);
        $enrollment = $goal->enrollment;

        return redirect()->route('enrollments.show', $enrollment)->with('success', '目標を更新しました');
    }

    public function destroy(EnrollmentGoal $goal)
    {
        $this->authorize('delete', $goal);

        $goal->delete();

        return back()->with('success', '目標を削除しました');
    }

    public function achieve(EnrollmentGoal $goal)
    {
        $goal->update(['achieved_at' => Carbon::now()]);

        return back();
    }

    public function unachieve(EnrollmentGoal $goal)
    {
        $goal->update(['achieved_at' => null]);

        return back();
    }
}
