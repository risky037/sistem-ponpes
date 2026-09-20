<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicYear extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function academic_enrollments(): HasMany
    {
        return $this->hasMany(AcademicEnrollment::class);
    }

    public function wali_kelas_assignments(): HasMany
    {
        return $this->hasMany(WaliKelasAssignment::class);
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

    public function calendarEvents(): HasMany
    {
        return $this->hasMany(AcademicCalendarEvent::class);
    }

    public function calendar_events(): HasMany
    {
        return $this->hasMany(AcademicCalendarEvent::class);
    }
}
