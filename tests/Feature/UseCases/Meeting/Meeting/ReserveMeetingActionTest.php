<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Meeting;

use App\Enums\MeetingStatus;
use App\Exceptions\MeetingQuota\InsufficientMeetingQuotaException;
use App\Models\Certification;
use App\Models\CoachAvailability;
use App\Models\Enrollment;
use App\Models\MeetingQuotaTransaction;
use App\Models\User;
use App\Notifications\NewMessageNotification;
use App\Services\CoachMeetingLoadService;
use App\Services\GoogleCalendarService;
use App\Services\MeetingAvailabilityService;
use App\Services\MeetingQuotaService;
use App\UseCases\Meeting\ReserveMeetingAction;
use App\UseCases\MeetingQuota\ConsumeQuotaAction;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ReserveMeetingActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_reserves_meeting_successfully_and_sends_notifications(): void
    {
        Notification::fake();

        $student = User::factory()->create();
        $coach = User::factory()->create(['meeting_url' => 'https://example.com/zoom']);
        $certification = Certification::factory()->create();
        $admin = User::factory()->admin()->create(); // 管理者ユーザーを用意

        $certification->coaches()->attach($coach->id, [
            'assigned_by_user_id' => $admin->id,
            'assigned_at' => now(),
        ]);

        $enrollment = Enrollment::factory()->create([
            'user_id' => $student->id,
            'certification_id' => $certification->id,
        ]);

        $scheduledAt = Carbon::parse('2026-10-10 10:00:00');

        CoachAvailability::factory()->create([
            'coach_id' => $coach->id,
            'day_of_week' => $scheduledAt->dayOfWeek,
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'is_active' => true,
        ]);

        $availabilityService = $this->createMock(MeetingAvailabilityService::class);
        $availabilityService->expects($this->once())->method('validateSlot');

        $coachLoadService = $this->createMock(CoachMeetingLoadService::class);
        $coachLoadService->method('leastLoadedCoach')->willReturn($coach);

        $quotaService = $this->createMock(MeetingQuotaService::class);
        $quotaService->method('remaining')->willReturn(1);

        $consumeAction = $this->createMock(ConsumeQuotaAction::class);
        $transaction = new MeetingQuotaTransaction(['id' => 100]);
        $consumeAction->method('__invoke')->willReturn($transaction);

        $googleCalendarService = $this->createMock(GoogleCalendarService::class);

        $action = new ReserveMeetingAction(
            $availabilityService,
            $coachLoadService,
            $quotaService,
            $consumeAction,
            $googleCalendarService
        );

        $meeting = $action->execute($enrollment, $scheduledAt, '面談のトピック');

        $this->assertEquals(MeetingStatus::Reserved, $meeting->status);
        $this->assertEquals($coach->id, $meeting->coach_id);
        $this->assertEquals($student->id, $meeting->student_id);

        Notification::assertSentTo([$student, $coach], NewMessageNotification::class);
    }

    public function test_it_throws_exception_when_quota_is_insufficient(): void
    {
        $student = User::factory()->create();
        $enrollment = Enrollment::factory()->create(['user_id' => $student->id]);

        $quotaService = $this->createMock(MeetingQuotaService::class);
        $quotaService->method('remaining')->willReturn(0);

        $action = new ReserveMeetingAction(
            $this->createMock(MeetingAvailabilityService::class),
            $this->createMock(CoachMeetingLoadService::class),
            $quotaService,
            $this->createMock(ConsumeQuotaAction::class),
            $this->createMock(GoogleCalendarService::class)
        );

        $this->expectException(InsufficientMeetingQuotaException::class);
        $action->execute($enrollment, now(), 'トピック');
    }
}
