<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Meeting;

use App\Models\Meeting;
use App\Models\User;
use App\Services\MeetingQuotaService;
use App\UseCases\Meeting\GetStudentMeetingListAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetStudentMeetingListActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_paginated_upcoming_meetings_for_student_by_default(): void
    {
        $student = User::factory()->create();
        Meeting::factory()->create([
            'student_id' => $student->id,
            'scheduled_at' => now()->addDay(),
        ]);

        $quotaService = $this->createMock(MeetingQuotaService::class);
        $quotaService->method('remaining')->with($student)->willReturn(3);

        $action = new GetStudentMeetingListAction($quotaService);
        $result = $action->execute($student);

        $this->assertArrayHasKey('meetings', $result);
        $this->assertArrayHasKey('filter', $result);
        $this->assertEquals('upcoming', $result['filter']);
        $this->assertEquals(3, $result['meetingsRemaining']);
        $this->assertCount(1, $result['meetings']);
    }
}
