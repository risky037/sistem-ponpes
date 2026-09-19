<?php

namespace App\Observers;

use App\Models\Kamar;

class KamarObserver
{
    /**
     * Handle the Kamar "creating" event.
     */
    public function creating(Kamar $kamar): void
    {
        $activity = class_basename($kamar).' '.$kamar->nama;
        $kamar->CreateLog('Creating '.$activity);
    }

    /**
     * Handle the Kamar "updating" event.
     */
    public function updating(Kamar $kamar): void
    {
        $activity = class_basename($kamar).' '.$kamar->nama;
        $kamar->CreateLog('Updating '.$activity);
    }

    /**
     * Handle the Kamar "deleting" event.
     */
    public function deleting(Kamar $kamar): void
    {
        $activity = class_basename($kamar).' '.$kamar->nama;
        $kamar->CreateLog('Deleting '.$activity);
    }
}
