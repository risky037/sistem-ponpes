<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssessmentDefinition extends Model
{
    use HasFactory;

    public const TYPE_UTS = 'UTS';

    public const TYPE_UAS = 'UAS';

    public const TYPE_TUGAS = 'Tugas';

    public const TYPE_PRAKTIK = 'Praktik';

    public const TYPE_HARIAN = 'Ulangan Harian';

    public const TYPE_LAINNYA = 'Lainnya';

    public const ALLOWED_TYPES = [
        self::TYPE_UTS,
        self::TYPE_UAS,
        self::TYPE_TUGAS,
        self::TYPE_PRAKTIK,
        self::TYPE_HARIAN,
        self::TYPE_LAINNYA,
    ];

    protected $table = 'assessment_definitions';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'weight' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForAcademicYear(Builder $query, int|AcademicYear $academicYear): Builder
    {
        $id = $academicYear instanceof AcademicYear ? $academicYear->id : $academicYear;

        return $query->where('academic_year_id', $id);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function academic_year(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function assessmentComponents(): HasMany
    {
        return $this->hasMany(AssessmentComponent::class, 'assessment_definition_id');
    }

    public function assessment_components(): HasMany
    {
        return $this->hasMany(AssessmentComponent::class, 'assessment_definition_id');
    }
}
