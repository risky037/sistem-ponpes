<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssessmentComponent extends Model
{
    use HasFactory;

    protected $table = 'assessment_components';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'weight' => 'integer',
        ];
    }

    public function teachingAssignment(): BelongsTo
    {
        return $this->belongsTo(TeachingAssignment::class, 'teaching_assignment_id');
    }

    public function teaching_assignment(): BelongsTo
    {
        return $this->belongsTo(TeachingAssignment::class, 'teaching_assignment_id');
    }

    public function assessmentDefinition(): BelongsTo
    {
        return $this->belongsTo(AssessmentDefinition::class, 'assessment_definition_id');
    }

    public function assessment_definition(): BelongsTo
    {
        return $this->belongsTo(AssessmentDefinition::class, 'assessment_definition_id');
    }

    public function studentAssessmentScores(): HasMany
    {
        return $this->hasMany(StudentAssessmentScore::class, 'assessment_component_id');
    }

    public function student_assessment_scores(): HasMany
    {
        return $this->hasMany(StudentAssessmentScore::class, 'assessment_component_id');
    }
}
