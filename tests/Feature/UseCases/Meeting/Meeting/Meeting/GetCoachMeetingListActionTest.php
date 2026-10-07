<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Meeting;

use App\Models\Meeting;
use App\Models\User;
use App\UseCases\Meeting\GetCoachMeetingListAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetCoachMeetingListActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_coach_meetings_filtered_by_student_and_status(): void
    {
        $coach = User::factory()->create();
        $student = User::factory()->create();

        Meeting::factory()->create([
            'coach_id' => $coach->id,
            'student_id' => $student->id,
            'scheduled_at' => now()->addDay(),
        ]);

        $action = new GetCoachMeetingListAction;
        $result = $action->execute($coach, [
            'filter' => 'upcoming',
            'student' => $student->id,
        ]);

        $this->assertEquals('upcoming', $result['filter']);
        $this->assertEquals($student->id, $result['studentFilter']);
        $this->assertCount(1, $result['meetings']);
    }
}
