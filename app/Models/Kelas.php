<?php

namespace App\Models;

use App\Traits\LogActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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

    public function classSchedules(): HasMany
    {
        return $this->hasMany(ClassSchedule::class);
    }

    public function class_schedules(): HasMany
    {
        return $this->hasMany(ClassSchedule::class);
    }

    public function primaryLearningMaterials(): HasMany
    {
        return $this->hasMany(LearningMaterial::class, 'kelas_id');
    }

    public function targetedLearningMaterials(): BelongsToMany
    {
        return $this->belongsToMany(LearningMaterial::class, 'learning_material_targets', 'kelas_id', 'learning_material_id')
            ->withTimestamps();
    }
}
