<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Meeting;

use App\Models\Meeting;
use App\UseCases\Meeting\GetMeetingDetailAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetMeetingDetailActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_loads_relations_for_meeting_detail(): void
    {
        $meeting = Meeting::factory()->create();

        $action = new GetMeetingDetailAction;
        $result = $action->execute($meeting);

        $this->assertTrue($result->relationLoaded('enrollment'));
        $this->assertTrue($result->relationLoaded('coach'));
        $this->assertTrue($result->relationLoaded('student'));
        $this->assertTrue($result->relationLoaded('canceledBy'));
        $this->assertTrue($result->relationLoaded('meetingMemo'));
    }
}
