<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\EnrollmentGoal;
use App\Models\User;

class EnrollmentGoalPolicy
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
    public function view(User $user, EnrollmentGoal $enrollmentGoal): bool
    {
        //
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->role === UserRole::Student;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, EnrollmentGoal $enrollmentGoal): bool
    {
        return $user->id === $enrollmentGoal->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, EnrollmentGoal $enrollmentGoal): bool
    {
        return $user->id === $enrollmentGoal->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, EnrollmentGoal $enrollmentGoal): bool
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, EnrollmentGoal $enrollmentGoal): bool
    {
        //
    }

    public function markAchieved(User $user, EnrollmentGoal $enrollmentGoal)
    {
        return $user->id === $enrollmentGoal->user_id
                && $enrollmentGoal->achieved_at === null;
    }

    public function unmarkAchieved(User $user, EnrollmentGoal $enrollmentGoal)
    {
        return $user->id === $enrollmentGoal->user_id
                && $enrollmentGoal->achieved_at !== null;
    }
}
