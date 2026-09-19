<?php

namespace App\Models;

use App\Traits\LogActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransaksiTabungan extends Model
{
    use HasFactory, LogActivity;

    protected $guarded = ['id'];

    public static function boot()
    {
        parent::boot();
        self::creating(function ($transaksi_tabungan) {
            $activity = class_basename($transaksi_tabungan).' '.$transaksi_tabungan->santri->user->name.' '.$transaksi_tabungan->jenis_transaksi.' '.$transaksi_tabungan->jumlah_transaksi;
            $transaksi_tabungan->CreateLog('Creating '.$activity);
        });

        self::updating(function ($transaksi_tabungan) {
            $activity = class_basename($transaksi_tabungan).' '.$transaksi_tabungan->santri->user->name.' '.$transaksi_tabungan->jenis_transaksi.' '.$transaksi_tabungan->jumlah_transaksi;
            $transaksi_tabungan->CreateLog('Updating '.$activity);
        });
        self::deleting(function ($transaksi_tabungan) {
            $activity = class_basename($transaksi_tabungan).' '.$transaksi_tabungan->santri->user->name.' '.$transaksi_tabungan->jenis_transaksi.' '.$transaksi_tabungan->jumlah_transaksi;
            $transaksi_tabungan->CreateLog('Deleting '.$activity);
        });
    }

    /**
     * Get the student associated with the savings transaction.
     */
    public function santri(): BelongsTo
    {
        return $this->belongsTo(Santri::class);
    }

    /**
     * Get the savings account associated with the transaction.
     */
    public function tabungan(): BelongsTo
    {
        return $this->belongsTo(
            Tabungan::class,
            'santri_id',
            'santri_id'
        );
    }
}
