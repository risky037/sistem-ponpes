<?php

namespace App\Models;

use App\Traits\LogActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transfer extends Model
{
    use HasFactory, LogActivity;

    protected $guarded = ['id'];

    /**
     * Get the sender santri.
     */
    public function pengirim(): BelongsTo
    {
        return $this->belongsTo(Santri::class, 'pengirim_id');
    }

    /**
     * Get the recipient santri.
     */
    public function penerima(): BelongsTo
    {
        return $this->belongsTo(Santri::class, 'penerima_id');
    }

    public static function boot()
    {
        parent::boot();
        self::creating(function ($transfer) {
            $activity = class_basename($transfer).' '.$transfer->jumlah_transfer;
            $transfer->CreateLog('Creating '.$activity);
        });

        self::updating(function ($transfer) {
            $activity = class_basename($transfer).' '.$transfer->jumlah_transfer;
            $transfer->CreateLog('Updating '.$activity);
        });
        self::deleting(function ($transfer) {
            $activity = class_basename($transfer).' '.$transfer->jumlah_transfer;
            $transfer->CreateLog('Deleting '.$activity);
        });
    }
}
