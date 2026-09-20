<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentBatch extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function santris(): HasMany
    {
        return $this->hasMany(Santri::class);
    }
}
