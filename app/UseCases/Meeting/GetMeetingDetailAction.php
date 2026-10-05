<?php

declare(strict_types=1);

namespace App\UseCases\Meeting;

use App\Models\Meeting;

class GetMeetingDetailAction
{
    public function execute(Meeting $meeting): Meeting
    {
        return $meeting->loadMissing([
            'enrollment.certification',
            'coach',
            'student',
            'canceledBy',
            'meetingMemo',
        ]);
    }
}