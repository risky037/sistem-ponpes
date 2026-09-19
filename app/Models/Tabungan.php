<?php

namespace App\Models;

use App\Traits\LogActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tabungan extends Model
{
    use HasFactory, LogActivity;

    protected $guarded = ['id'];

    public static function boot()
    {
        parent::boot();
        self::creating(function ($tabungan) {
            $activity = class_basename($tabungan).' '.$tabungan->santri->user->name;
            $tabungan->CreateLog('Creating '.$activity);
        });

        self::updating(function ($tabungan) {
            $activity = class_basename($tabungan).' '.$tabungan->santri->user->name;
            $tabungan->CreateLog('Updating '.$activity);
        });
        self::deleting(function ($tabungan) {
            $activity = class_basename($tabungan).' '.$tabungan->santri->user->name;
            $tabungan->CreateLog('Deleting '.$activity);
        });
    }

    /**
     * Get the student that owns the savings account.
     */
    public function santri(): BelongsTo
    {
        return $this->belongsTo(Santri::class);
    }

    /**
     * Get all transactions associated with this savings account.
     */
    public function transaksi(): HasMany
    {
        return $this->hasMany(
            TransaksiTabungan::class,
            'santri_id',
            'santri_id'
        );
    }
}
