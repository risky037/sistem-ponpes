<?php

namespace App\Observers;

use App\Models\User;

class UserObserver
{
    /**
     * Handle the User "creating" event.
     */
    public function creating(User $user): void
    {
        $activity = class_basename($user)." $user->name";
        $user->CreateLog('Creatting '.$activity);
    }

    /**
     * Handle the User "updating" event.
     */
    public function updating(User $user): void
    {
        $activity = class_basename($user)." $user->name";
        $user->CreateLog('Updating '.$activity);
    }

    /**
     * Handle the User "deleting" event.
     */
    public function deleting(User $user): void
    {
        $activity = class_basename($user)." $user->name";
        $user->CreateLog('Deleting '.$activity);
    }
}
