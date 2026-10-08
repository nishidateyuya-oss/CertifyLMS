<?php

namespace App\Observers;

use App\Models\Enrollment;
use App\Services\EnrollmentStatsService;

class EnrollmentObserver
{
    /**
     * Handle the Enrollment "created" event.
     */
    public function created(Enrollment $enrollment): void
    {
        EnrollmentStatsService::clearCache();
    }

    /**
     * Handle the Enrollment "updated" event.
     */
    public function updated(Enrollment $enrollment): void
    {
        if ($enrollment->wasChanged('status')) {
            EnrollmentStatsService::clearCache();
        }
    }

    /**
     * Handle the Enrollment "deleted" event.
     */
    public function deleted(Enrollment $enrollment): void
    {
        EnrollmentStatsService::clearCache();
    }

    /**
     * Handle the Enrollment "restored" event.
     */
    public function restored(Enrollment $enrollment): void
    {
        EnrollmentStatsService::clearCache();
    }

    /**
     * Handle the Enrollment "force deleted" event.
     */
    public function forceDeleted(Enrollment $enrollment): void
    {
        //
    }
}
