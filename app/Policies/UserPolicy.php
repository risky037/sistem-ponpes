<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view the target user profile.
     */
    public function view(User $currentUser, User $targetUser): bool
    {
        return $currentUser->id === $targetUser->id || $currentUser->hasRole('Administrator');
    }

    /**
     * Determine whether the user can update the target user's account.
     */
    public function updateAccount(User $currentUser, User $targetUser): bool
    {
        return $currentUser->id === $targetUser->id || $currentUser->hasRole('Administrator');
    }

    /**
     * Determine whether the user can update the target user's biodata.
     */
    public function updateBiodata(User $currentUser, User $targetUser): bool
    {
        return $currentUser->id === $targetUser->id || $currentUser->hasRole('Administrator');
    }

    /**
     * Determine whether the user can delete the target user.
     */
    public function delete(User $currentUser, User $targetUser): bool
    {
        if ($currentUser->id === $targetUser->id) {
            return false;
        }

        if ($targetUser->hasRole('Administrator') && User::role('Administrator')->count() <= 1) {
            return false;
        }

        return $currentUser->hasRole('Administrator');
    }
}
