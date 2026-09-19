<?php

namespace App\Observers;

use App\Models\Transfer;

class TransferObserver
{
    /**
     * Handle the Transfer "creating" event.
     */
    public function creating(Transfer $transfer): void
    {
        $activity = class_basename($transfer).' '.$transfer->jumlah_transfer;
        $transfer->CreateLog('Creating '.$activity);
    }

    /**
     * Handle the Transfer "updating" event.
     */
    public function updating(Transfer $transfer): void
    {
        $activity = class_basename($transfer).' '.$transfer->jumlah_transfer;
        $transfer->CreateLog('Updating '.$activity);
    }

    /**
     * Handle the Transfer "deleting" event.
     */
    public function deleting(Transfer $transfer): void
    {
        $activity = class_basename($transfer).' '.$transfer->jumlah_transfer;
        $transfer->CreateLog('Deleting '.$activity);
    }
}
