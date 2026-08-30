<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\QaThreadStatus;
use App\Enums\UserRole;
use App\Models\QaReply;
use App\Models\QaThread;
use App\Models\User;

class QaReplyPolicy
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
    public function view(User $user, QaReply $qaReply): bool
    {
        //
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, QaThread $thread): bool
    {
        if ($thread->status === QaThreadStatus::Resolved) {
            return false;
        }

        return $user->role === UserRole::Student
            || $user->role === UserRole::Coach;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, QaReply $qaReply): bool
    {
        return $user->id === $qaReply->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, QaReply $qaReply): bool
    {
        return $user->id === $qaReply->user_id
                || $user->role === UserRole::Admin;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, QaReply $qaReply): bool
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, QaReply $qaReply): bool
    {
        //
    }
}
