<?php

namespace App\Observers;

use App\Models\Kelas;

class KelasObserver
{
    /**
     * Handle the Kelas "creating" event.
     */
    public function creating(Kelas $kelas): void
    {
        $activity = class_basename($kelas).' '.$kelas->tingkatan.' '.$kelas->kelas;
        $kelas->CreateLog('Creating '.$activity);
    }

    /**
     * Handle the Kelas "updating" event.
     */
    public function updating(Kelas $kelas): void
    {
        $activity = class_basename($kelas).' '.$kelas->tingkatan.' '.$kelas->kelas;
        $kelas->CreateLog('Updating '.$activity);
    }

    /**
     * Handle the Kelas "deleting" event.
     */
    public function deleting(Kelas $kelas): void
    {
        $activity = class_basename($kelas).' '.$kelas->tingkatan.' '.$kelas->kelas;
        $kelas->CreateLog('Deleting '.$activity);
    }
}
