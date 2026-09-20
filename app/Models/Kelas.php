<?php

namespace App\Models;

use App\Traits\LogActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kelas extends Model
{
    use HasFactory, LogActivity;

    protected $guarded = ['id'];

    public function academic_enrollments(): HasMany
    {
        return $this->hasMany(AcademicEnrollment::class);
    }

    public function wali_kelas_assignments(): HasMany
    {
        return $this->hasMany(WaliKelasAssignment::class);
    }

    public function mapels(): HasMany
    {
        return $this->hasMany(Mapel::class);
    }

    public function teaching_assignments(): HasMany
    {
        return $this->hasMany(TeachingAssignment::class);
    }
}
