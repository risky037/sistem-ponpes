<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    use HasFactory;

    public const STATUS_HADIR = 'Hadir';

    public const STATUS_IZIN = 'Izin';

    public const STATUS_SAKIT = 'Sakit';

    public const STATUS_ALPHA = 'Alpha';

    public const ALLOWED_STATUSES = [
        self::STATUS_HADIR,
        self::STATUS_IZIN,
        self::STATUS_SAKIT,
        self::STATUS_ALPHA,
    ];

    protected $table = 'attendance_records';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'marked_at' => 'datetime',
        ];
    }

    public function scopePresent(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_HADIR);
    }

    public function scopeAbsent(Builder $query): Builder
    {
        return $query->whereIn('status', [
            self::STATUS_IZIN,
            self::STATUS_SAKIT,
            self::STATUS_ALPHA,
        ]);
    }

    public function scopeForSession(Builder $query, int|TeachingSession $session): Builder
    {
        $sessionId = $session instanceof TeachingSession ? $session->id : $session;

        return $query->where('teaching_session_id', $sessionId);
    }

    public function teachingSession(): BelongsTo
    {
        return $this->belongsTo(TeachingSession::class, 'teaching_session_id');
    }

    public function teaching_session(): BelongsTo
    {
        return $this->belongsTo(TeachingSession::class, 'teaching_session_id');
    }

    public function academicEnrollment(): BelongsTo
    {
        return $this->belongsTo(AcademicEnrollment::class, 'academic_enrollment_id');
    }

    public function academic_enrollment(): BelongsTo
    {
        return $this->belongsTo(AcademicEnrollment::class, 'academic_enrollment_id');
    }

    public function marker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }
}
