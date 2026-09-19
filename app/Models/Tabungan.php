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
