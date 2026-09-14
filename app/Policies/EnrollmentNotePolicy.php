<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Enrollment;
use App\Models\EnrollmentNote;
use App\Models\User;

class EnrollmentNotePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Admin || $user->role === UserRole::Coach;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, EnrollmentNote $enrollmentNote): bool
    {
        //
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Enrollment $enrollment): bool
    {
        if ($user->role === UserRole::Admin) {
            return true;
        }

        return $enrollment->certification->coaches()->where('users.id', $user->id)->exists();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, EnrollmentNote $enrollmentNote): bool
    {
        if ($user->role === UserRole::Admin) {
            return true;
        }

        return $user->id === $enrollmentNote->author_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, EnrollmentNote $enrollmentNote): bool
    {
        if ($user->role === UserRole::Admin) {
            return true;
        }

        return $user->id === $enrollmentNote->author_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, EnrollmentNote $enrollmentNote): bool
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, EnrollmentNote $enrollmentNote): bool
    {
        //
    }
}
