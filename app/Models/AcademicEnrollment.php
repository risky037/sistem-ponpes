<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicEnrollment extends Model
{
    use HasFactory;

    public const STATUS_AKTIF = 'Aktif';

    public const STATUS_NONAKTIF = 'Nonaktif';

    public const STATUS_LULUS = 'Lulus';

    public const STATUS_PINDAH = 'Pindah';

    public const ALLOWED_STATUSES = [
        self::STATUS_AKTIF,
        self::STATUS_NONAKTIF,
        self::STATUS_LULUS,
        self::STATUS_PINDAH,
    ];

    protected $guarded = ['id'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'enrolled_at' => 'date',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_AKTIF);
    }

    public function academic_year(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function santri(): BelongsTo
    {
        return $this->belongsTo(Santri::class);
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class, 'academic_enrollment_id');
    }

    public function attendance_records(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class, 'academic_enrollment_id');
    }
}
