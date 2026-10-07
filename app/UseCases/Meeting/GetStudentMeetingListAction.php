<?php

declare(strict_types=1);

namespace App\UseCases\Meeting;

use App\Models\Meeting;
use App\Models\User;
use App\Services\MeetingQuotaService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetStudentMeetingListAction
{
    public function __construct(
        private readonly MeetingQuotaService $meetingQuotaService
    ) {}

    /**
     * @return array{meetings: LengthAwarePaginator, filter: string, meetingsRemaining: int}
     */
    public function execute(User $user, ?string $filter = null): array
    {
        $filter = $filter ?? 'upcoming';

        $query = Meeting::query()
            ->with(['enrollment.certification', 'coach'])
            ->forStudent($user)
            ->orderByDesc('scheduled_at');

        $meetings = match ($filter) {
            'past' => $query->past()->paginate(20),
            'all' => $query->paginate(20),
            default => $query->upcoming()->paginate(20),
        };

        return [
            'meetings' => $meetings,
            'filter' => $filter,
            'meetingsRemaining' => $this->meetingQuotaService->remaining($user),
        ];
    }
}
