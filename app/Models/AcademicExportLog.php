<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicExportLog extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    public const TYPE_ENROLLMENT = 'enrollment';

    public const TYPE_TEACHING_ASSIGNMENT = 'teaching_assignment';

    public const TYPE_ATTENDANCE = 'attendance';

    public const TYPE_ASSESSMENT = 'assessment';

    public const TYPE_PERFORMANCE = 'performance';

    public const FORMAT_XLSX = 'xlsx';

    public const FORMAT_PRINT = 'print';

    protected $fillable = [
        'user_id',
        'export_type',
        'academic_year_id',
        'kelas_id',
        'format',
        'filter_payload',
        'records_count',
        'ip_address',
        'created_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'filter_payload' => 'array',
            'created_at' => 'datetime',
            'records_count' => 'integer',
        ];
    }

    /**
     * User who executed the export.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Academic year contextual anchor.
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Kelas contextual anchor.
     */
    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    /**
     * Scope query to a specific user.
     */
    public function scopeForUser(Builder $query, int|string $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope query to a specific academic year.
     */
    public function scopeForAcademicYear(Builder $query, int|string $academicYearId): Builder
    {
        return $query->where('academic_year_id', $academicYearId);
    }

    /**
     * Scope query to a specific export type.
     */
    public function scopeOfType(Builder $query, string $exportType): Builder
    {
        return $query->where('export_type', $exportType);
    }
}
