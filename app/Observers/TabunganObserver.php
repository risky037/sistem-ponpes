<?php

namespace App\Observers;

use App\Models\Tabungan;

class TabunganObserver
{
    /**
     * Handle the Tabungan "creating" event.
     */
    public function creating(Tabungan $tabungan): void
    {
        $activity = class_basename($tabungan).' '.$tabungan->santri?->user?->name;
        $tabungan->CreateLog('Creating '.$activity);
    }

    /**
     * Handle the Tabungan "updating" event.
     */
    public function updating(Tabungan $tabungan): void
    {
        $activity = class_basename($tabungan).' '.$tabungan->santri?->user?->name;
        $tabungan->CreateLog('Updating '.$activity);
    }

    /**
     * Handle the Tabungan "deleting" event.
     */
    public function deleting(Tabungan $tabungan): void
    {
        $activity = class_basename($tabungan).' '.$tabungan->santri?->user?->name;
        $tabungan->CreateLog('Deleting '.$activity);
    }
}
