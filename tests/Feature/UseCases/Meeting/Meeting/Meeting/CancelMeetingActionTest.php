<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Meeting;

use App\Enums\MeetingStatus;
use App\Exceptions\Mentoring\MeetingAlreadyStartedException;
use App\Models\Meeting;
use App\Models\User;
use App\Services\GoogleCalendarService;
use App\UseCases\Meeting\CancelMeetingAction;
use App\UseCases\MeetingQuota\RefundQuotaAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CancelMeetingActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_cancels_meeting_and_refunds_quota(): void
    {
        $student = User::factory()->create();
        $actor = User::factory()->create();

        $meeting = Meeting::factory()->create([
            'student_id' => $student->id,
            'status' => MeetingStatus::Reserved->value,
            'scheduled_at' => now()->addHours(2),
        ]);

        $refundAction = $this->createMock(RefundQuotaAction::class);
        $refundAction->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(fn ($user) => $user->id === $student->id),
                $meeting->id);

        $googleCalendarService = $this->createMock(GoogleCalendarService::class);

        $action = new CancelMeetingAction($refundAction, $googleCalendarService);
        $action->execute($meeting, $actor);

        $meeting->refresh();
        $this->assertEquals(MeetingStatus::Canceled, $meeting->status);
        $this->assertEquals($actor->id, $meeting->canceled_by_user_id);
        $this->assertNotNull($meeting->canceled_at);
    }

    public function test_it_throws_exception_when_canceling_past_meeting(): void
    {
        $actor = User::factory()->create();
        $meeting = Meeting::factory()->create([
            'status' => MeetingStatus::Reserved->value,
            'scheduled_at' => now()->subHour(),
        ]);

        $action = new CancelMeetingAction(
            $this->createMock(RefundQuotaAction::class),
            $this->createMock(GoogleCalendarService::class)
        );

        $this->expectException(MeetingAlreadyStartedException::class);
        $action->execute($meeting, $actor);
    }
}
