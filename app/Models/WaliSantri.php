<?php

namespace App\Models;

use App\Traits\LogActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaliSantri extends Model
{
    use HasFactory, LogActivity;

    protected $guarded = ['id'];

    public function santri()
    {
        return $this->belongsTo(Santri::class);
    }
}
