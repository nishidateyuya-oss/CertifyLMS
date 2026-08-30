<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\QaThreadStatus;
use App\Enums\UserRole;
use App\Models\QaThread;
use App\Models\User;

class QaThreadPolicy
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
    public function view(User $user, QaThread $qaThread): bool
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
    public function update(User $user, QaThread $qaThread): bool
    {
        return $user->id === $qaThread->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, QaThread $qaThread): bool
    {
        return $user->id === $qaThread->user_id
                || $user->role === UserRole::Admin;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, QaThread $qaThread): bool
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, QaThread $qaThread): bool
    {
        //
    }

    public function resolve(User $user, QaThread $qaThread): bool
    {
        return $qaThread->status === QaThreadStatus::Unresolved
            && $user->id === $qaThread->user_id;
    }

    public function unresolve(User $user, QaThread $qaThread): bool
    {
        return $qaThread->status === QaThreadStatus::Resolved
            && $user->id === $qaThread->user_id;
    }
}
