<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassSchedule extends Model
{
    use HasFactory;

    public const DAYS_OF_WEEK = [
        'Senin',
        'Selasa',
        'Rabu',
        'Kamis',
        'Jumat',
        'Sabtu',
        'Ahad',
    ];

    protected $table = 'class_schedules';

    protected $guarded = ['id'];

    /**
     * Normalize time strings to H:i:s across all database engines.
     */
    public static function normalizeTime(?string $time): ?string
    {
        if ($time === null || $time === '') {
            return null;
        }

        $timestamp = strtotime($time);
        if ($timestamp === false) {
            return $time;
        }

        return date('H:i:s', $timestamp);
    }

    /**
     * Start time attribute ensuring normalized H:i:s storage and retrieval.
     */
    protected function startTime(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => self::normalizeTime($value),
            set: fn (?string $value) => self::normalizeTime($value),
        );
    }

    /**
     * End time attribute ensuring normalized H:i:s storage and retrieval.
     */
    protected function endTime(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => self::normalizeTime($value),
            set: fn (?string $value) => self::normalizeTime($value),
        );
    }

    /**
     * Formatted start time in H:i format for UI rendering.
     */
    public function getFormattedStartTimeAttribute(): string
    {
        return $this->start_time ? substr($this->start_time, 0, 5) : '-';
    }

    /**
     * Formatted end time in H:i format for UI rendering.
     */
    public function getFormattedEndTimeAttribute(): string
    {
        return $this->end_time ? substr($this->end_time, 0, 5) : '-';
    }

    /**
     * Formatted time range in H:i - H:i format.
     */
    public function getTimeRangeAttribute(): string
    {
        if (! $this->start_time || ! $this->end_time) {
            return '-';
        }

        return $this->formatted_start_time.' - '.$this->formatted_end_time;
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    public function teachingAssignment(): BelongsTo
    {
        return $this->belongsTo(TeachingAssignment::class, 'teaching_assignment_id');
    }

    public function teaching_assignment(): BelongsTo
    {
        return $this->belongsTo(TeachingAssignment::class, 'teaching_assignment_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function academic_year(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function teachingSessions(): HasMany
    {
        return $this->hasMany(TeachingSession::class, 'class_schedule_id');
    }

    public function teaching_sessions(): HasMany
    {
        return $this->hasMany(TeachingSession::class, 'class_schedule_id');
    }
}
