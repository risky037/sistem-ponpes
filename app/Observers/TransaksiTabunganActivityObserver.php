<?php

namespace App\Observers;

use App\Models\TransaksiTabungan;

class TransaksiTabunganActivityObserver
{
    /**
     * Handle the TransaksiTabungan "creating" event.
     */
    public function creating(TransaksiTabungan $transaksiTabungan): void
    {
        $santriName = $transaksiTabungan->santri?->user?->name ?? 'Santri';
        $activity = class_basename($transaksiTabungan).' '.$santriName.' '.$transaksiTabungan->jenis_transaksi.' '.$transaksiTabungan->jumlah_transaksi;
        $transaksiTabungan->CreateLog('Creating '.$activity);
    }

    /**
     * Handle the TransaksiTabungan "updating" event.
     */
    public function updating(TransaksiTabungan $transaksiTabungan): void
    {
        $santriName = $transaksiTabungan->santri?->user?->name ?? 'Santri';
        $activity = class_basename($transaksiTabungan).' '.$santriName.' '.$transaksiTabungan->jenis_transaksi.' '.$transaksiTabungan->jumlah_transaksi;
        $transaksiTabungan->CreateLog('Updating '.$activity);
    }

    /**
     * Handle the TransaksiTabungan "deleting" event.
     */
    public function deleting(TransaksiTabungan $transaksiTabungan): void
    {
        $santriName = $transaksiTabungan->santri?->user?->name ?? 'Santri';
        $activity = class_basename($transaksiTabungan).' '.$santriName.' '.$transaksiTabungan->jenis_transaksi.' '.$transaksiTabungan->jumlah_transaksi;
        $transaksiTabungan->CreateLog('Deleting '.$activity);
    }
}
