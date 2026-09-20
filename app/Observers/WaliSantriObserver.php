<?php

namespace App\Observers;

use App\Models\WaliSantri;

class WaliSantriObserver
{
    /**
     * Handle the WaliSantri "creating" event.
     */
    public function creating(WaliSantri $wali): void
    {
        $wali->CreateLog('Creating '.class_basename($wali));
    }

    /**
     * Handle the WaliSantri "updating" event.
     */
    public function updating(WaliSantri $wali): void
    {
        $wali->CreateLog('Updating '.class_basename($wali));
    }

    /**
     * Handle the WaliSantri "deleting" event.
     */
    public function deleting(WaliSantri $wali): void
    {
        $wali->CreateLog('Deleting '.class_basename($wali));
    }
}
