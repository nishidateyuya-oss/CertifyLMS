<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\EnrollmentStatus;
use App\Http\Requests\Meeting\AvailabilityRequest;
use App\Http\Requests\Meeting\IndexAsCoachRequest;
use App\Http\Requests\Meeting\IndexRequest;
use App\Http\Requests\Meeting\StoreRequest;
use App\Http\Requests\Meeting\UpsertMemoRequest;
use App\Models\Enrollment;
use App\Models\Meeting;
use App\Services\MeetingQuotaService;
use App\UseCases\Meeting\CancelMeetingAction;
use App\UseCases\Meeting\FetchMeetingAvailabilityAction;
use App\UseCases\Meeting\GetCoachMeetingListAction;
use App\UseCases\Meeting\GetMeetingDetailAction;
use App\UseCases\Meeting\GetStudentMeetingListAction;
use App\UseCases\Meeting\ReserveMeetingAction;
use App\UseCases\Meeting\UpsertMeetingMemoAction;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * 1on1 面談予約 (Meeting) の HTTP エントリポイント。
 *
 * 各メソッドは受付、認可委譲、および Action クラスへの処理移譲とレスポンス整形のみを行う。
 */
class MeetingController extends Controller
{
    /**
     * 受講生本人の面談一覧。filter (upcoming/past/all) クエリで履歴を切り替える。
     */
    public function index(IndexRequest $request, GetStudentMeetingListAction $action): View
    {
        $data = $action->execute($request->user(), $request->validated('filter'));

        return view('meeting.index', $data);
    }

    /**
     * コーチ宛の面談一覧。担当受講生 / 受講登録での絞り込みを併せて提供する。
     */
    public function indexAsCoach(IndexAsCoachRequest $request, GetCoachMeetingListAction $action): View
    {
        $data = $action->execute($request->user(), $request->validated());

        return view('meeting.coach.index', $data);
    }

    /**
     * 面談詳細(当事者共通)。Policy で coach/student の閲覧範囲を絞る。
     */
    public function show(Meeting $meeting, GetMeetingDetailAction $action): View
    {
        $this->authorize('view', $meeting);

        return view('meeting.show', [
            'meeting' => $action->execute($meeting),
        ]);
    }

    /**
     * 予約画面(受講生): URL に Enrollment を含む正規ルートで表示する。
     */
    public function create(Enrollment $enrollment, MeetingQuotaService $meetingQuota): View
    {
        $this->authorize('create', Meeting::class);

        abort_unless($enrollment->user_id === auth()->id(), 403);
        abort_unless($enrollment->status === EnrollmentStatus::Learning, 403);

        $enrollment->loadMissing('certification');

        return view('meeting.create', [
            'enrollment' => $enrollment,
            'meetingsRemaining' => $meetingQuota->remaining(auth()->user()),
        ]);
    }

    /**
     * 予約画面のエントリポイント(URL に Enrollment 無し)。
     */
    public function createFallback(): View
    {
        $user = auth()->user();
        $enrollments = $user
            ?->enrollments()
            ->whereIn('status', [EnrollmentStatus::Learning->value, EnrollmentStatus::Passed->value])
            ->with('certification')
            ->get();

        return view('meeting.empty-state', [
            'enrollments' => $enrollments ?? collect(),
        ]);
    }

    /**
     * 受講生の予約申請。
     */
    public function store(
        Enrollment $enrollment,
        StoreRequest $request,
        ReserveMeetingAction $action,
    ): RedirectResponse {
        $scheduledAt = Carbon::parse($request->validated('scheduled_at'));
        $topic = $request->validated('topic');

        $meeting = $action->execute($enrollment, $scheduledAt, $topic);

        return redirect()
            ->route('meetings.show', $meeting)
            ->with('success', '面談を予約しました。');
    }

    /**
     * 当事者(受講生 or コーチ)による面談キャンセル。
     */
    public function cancel(Meeting $meeting, CancelMeetingAction $action): RedirectResponse
    {
        $this->authorize('cancel', $meeting);

        $action->execute($meeting, auth()->user());

        return redirect()
            ->route('meetings.show', $meeting)
            ->with('success', '面談をキャンセルしました。面談回数を返却しました。');
    }

    /**
     * 担当コーチによる面談メモ作成・更新。
     */
    public function upsertMemo(
        Meeting $meeting,
        UpsertMemoRequest $request,
        UpsertMeetingMemoAction $action,
    ): RedirectResponse {
        $action->execute($meeting, $request->validated('body'));

        return redirect()
            ->route('meetings.show', $meeting)
            ->with('success', '面談メモを保存しました。');
    }

    /**
     * 予約画面が呼ぶ空き枠取得 JSON エンドポイント。
     */
    public function fetchAvailability(
        Enrollment $enrollment,
        AvailabilityRequest $request,
        FetchMeetingAvailabilityAction $action,
    ): JsonResponse {
        $date = Carbon::parse($request->validated('date'));

        return response()->json($action->execute($enrollment, $date));
    }
}
