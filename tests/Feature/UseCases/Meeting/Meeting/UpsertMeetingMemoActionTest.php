<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Meeting;

use App\Enums\MeetingStatus;
use App\Exceptions\Mentoring\MeetingStatusTransitionException;
use App\Models\Meeting;
use App\UseCases\Meeting\UpsertMeetingMemoAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpsertMeetingMemoActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_or_updates_memo_for_reserved_meeting(): void
    {
        $meeting = Meeting::factory()->create([
            'status' => MeetingStatus::Reserved->value,
        ]);

        $action = new UpsertMeetingMemoAction;
        $action->execute($meeting, 'これはメモの内容です。');

        $this->assertDatabaseHas('meeting_memos', [
            'meeting_id' => $meeting->id,
            'body' => 'これはメモの内容です。',
        ]);
    }

    public function test_it_throws_exception_when_meeting_is_canceled(): void
    {
        $meeting = Meeting::factory()->create([
            'status' => MeetingStatus::Canceled->value,
        ]);

        $action = new UpsertMeetingMemoAction;

        $this->expectException(MeetingStatusTransitionException::class);
        $action->execute($meeting, 'テストメモ');
    }
}
