<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicKpiSnapshot extends Model
{
    use HasFactory;

    public const TYPE_INSTITUTIONAL_KPI = 'institutional_kpi';

    public const TYPE_ATTENDANCE_ANALYTICS = 'attendance_analytics';

    public const TYPE_GRADE_DISTRIBUTION = 'grade_distribution';

    public const TYPE_TEACHER_WORKLOAD = 'teacher_workload';

    public const TYPE_OPERATIONAL_HEALTH = 'operational_health';

    public const ALLOWED_TYPES = [
        self::TYPE_INSTITUTIONAL_KPI,
        self::TYPE_ATTENDANCE_ANALYTICS,
        self::TYPE_GRADE_DISTRIBUTION,
        self::TYPE_TEACHER_WORKLOAD,
        self::TYPE_OPERATIONAL_HEALTH,
    ];

    protected $table = 'academic_kpi_snapshots';

    protected $guarded = ['id'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'snapshot_date' => 'date',
            'metrics' => 'array',
        ];
    }

    public function scopeForAcademicYear(Builder $query, int|AcademicYear $academicYear): Builder
    {
        $id = $academicYear instanceof AcademicYear ? $academicYear->id : $academicYear;

        return $query->where('academic_year_id', $id);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('snapshot_type', $type);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function academic_year(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function capturedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'captured_by');
    }

    public function captured_by(): BelongsTo
    {
        return $this->belongsTo(User::class, 'captured_by');
    }
}
