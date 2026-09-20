<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicPerformanceSummary extends Model
{
    use HasFactory;

    public const STATUS_LENGKAP = 'Lengkap';

    public const STATUS_SEBAGIAN = 'Sebagian';

    public const STATUS_KOSONG = 'Kosong';

    public const CURRENT_SOURCE_VERSION = 'v1.0';

    public const ALLOWED_STATUSES = [
        self::STATUS_LENGKAP,
        self::STATUS_SEBAGIAN,
        self::STATUS_KOSONG,
    ];

    protected $table = 'academic_performance_summaries';

    protected $guarded = ['id'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'attendance_rate' => 'decimal:2',
            'weighted_score_sum' => 'decimal:2',
            'average_score' => 'decimal:2',
            'computed_at' => 'datetime',
        ];
    }

    public function scopeComplete(Builder $query): Builder
    {
        return $query->where('computation_status', self::STATUS_LENGKAP);
    }

    public function scopePartial(Builder $query): Builder
    {
        return $query->where('computation_status', self::STATUS_SEBAGIAN);
    }

    public function scopeEmpty(Builder $query): Builder
    {
        return $query->where('computation_status', self::STATUS_KOSONG);
    }

    public function academicEnrollment(): BelongsTo
    {
        return $this->belongsTo(AcademicEnrollment::class, 'academic_enrollment_id');
    }

    public function academic_enrollment(): BelongsTo
    {
        return $this->belongsTo(AcademicEnrollment::class, 'academic_enrollment_id');
    }
}
