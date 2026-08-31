<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\MeetingPackStatus;
use App\Enums\UserRole;
use App\Models\MeetingPack;
use App\Models\User;

class MeetingPackPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        //
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, MeetingPack $meetingPack): bool
    {
        //
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        //
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, MeetingPack $meetingPack): bool
    {
        return $user->role === UserRole::Admin;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, MeetingPack $meetingPack): bool
    {
        if ($meetingPack->status === MeetingPackStatus::Published) {
            return false;
        }

        return $user->role === UserRole::Admin;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, MeetingPack $meetingPack): bool
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, MeetingPack $meetingPack): bool
    {
        //
    }

    public function publish(User $user, MeetingPack $plan)
    {
        return $plan->status === MeetingPackStatus::Draft;
    }

    public function archive(User $user, MeetingPack $plan)
    {
        return $plan->status === MeetingPackStatus::Published;
    }

    public function unarchive(User $user, MeetingPack $plan)
    {
        return $plan->status === MeetingPackStatus::Archived;
    }
}
