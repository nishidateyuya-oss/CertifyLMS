<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Meeting;

use App\Models\Enrollment;
use App\Services\GoogleCalendarService;
use App\Services\MeetingAvailabilityService;
use App\UseCases\Meeting\FetchMeetingAvailabilityAction;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FetchMeetingAvailabilityActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_availability_slots(): void
    {
        $enrollment = Enrollment::factory()->create();
        $date = Carbon::parse('2026-10-10');

        $slotStart = Carbon::parse('2026-10-10 10:00:00');
        $slotEnd = Carbon::parse('2026-10-10 10:30:00');

        $availabilityService = $this->createMock(MeetingAvailabilityService::class);
        $availabilityService->method('slotsForCertification')->willReturn(collect([
            [
                'slot_start' => $slotStart,
                'slot_end' => $slotEnd,
                'available_coach_count' => 2,
            ],
        ]));

        $googleCalendarService = $this->createMock(GoogleCalendarService::class);

        $action = new FetchMeetingAvailabilityAction($availabilityService, $googleCalendarService);
        $result = $action->execute($enrollment, $date);

        $this->assertEquals('2026-10-10', $result['date']);
        $this->assertCount(1, $result['slots']);
        $this->assertEquals(2, $result['slots'][0]['available_coach_count']);
    }
}
