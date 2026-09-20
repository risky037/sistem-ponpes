<?php

namespace App\Services\Academic;

use App\Models\AcademicEnrollment;
use App\Models\AcademicYear;
use App\Models\AssessmentComponent;
use App\Models\AssessmentDefinition;
use App\Models\StudentAssessmentScore;
use App\Models\TeachingAssignment;
use App\Models\User;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AssessmentService
{
    /**
     * Create a new assessment definition for an academic year.
     *
     * @throws DomainException
     */
    public function createDefinition(AcademicYear $academicYear, array $data): AssessmentDefinition
    {
        $name = trim($data['name'] ?? '');
        if ($name === '') {
            throw new DomainException('Nama definisi penilaian tidak boleh kosong.');
        }

        $existing = AssessmentDefinition::where('academic_year_id', $academicYear->id)
            ->where('name', $name)
            ->exists();

        if ($existing) {
            throw new DomainException("Definisi penilaian dengan nama '{$name}' sudah ada pada tahun ajaran ini.");
        }

        $type = $data['type'] ?? '';
        $this->validateType($type);

        $weight = (int) ($data['weight'] ?? 0);
        $this->validateWeight($weight);

        return DB::transaction(function () use ($academicYear, $name, $type, $weight, $data) {
            return AssessmentDefinition::create([
                'academic_year_id' => $academicYear->id,
                'name' => $name,
                'type' => $type,
                'weight' => $weight,
                'is_active' => $data['is_active'] ?? true,
            ]);
        });
    }

    /**
     * Update an assessment definition.
     *
     * @throws DomainException
     */
    public function updateDefinition(AssessmentDefinition $definition, array $data): AssessmentDefinition
    {
        $updateData = [];

        if (isset($data['name'])) {
            $name = trim($data['name']);
            if ($name === '') {
                throw new DomainException('Nama definisi penilaian tidak boleh kosong.');
            }

            $duplicate = AssessmentDefinition::where('academic_year_id', $definition->academic_year_id)
                ->where('name', $name)
                ->where('id', '!=', $definition->id)
                ->exists();

            if ($duplicate) {
                throw new DomainException("Definisi penilaian dengan nama '{$name}' sudah ada pada tahun ajaran ini.");
            }

            $updateData['name'] = $name;
        }

        if (isset($data['type'])) {
            $this->validateType($data['type']);
            $updateData['type'] = $data['type'];
        }

        if (isset($data['weight'])) {
            $weight = (int) $data['weight'];
            $this->validateWeight($weight);
            $updateData['weight'] = $weight;
        }

        if (array_key_exists('is_active', $data)) {
            $updateData['is_active'] = (bool) $data['is_active'];
        }

        return DB::transaction(function () use ($definition, $updateData) {
            $definition->update($updateData);

            return $definition->fresh(['academicYear', 'assessmentComponents']);
        });
    }

    /**
     * Delete or deactivate definition per Q3 architectural decision.
     * If no components exist: hard delete.
     * If components or historical scores exist: deactivate using is_active=false.
     *
     * @return bool True if hard-deleted, false if deactivated.
     */
    public function deactivateDefinition(AssessmentDefinition $definition): bool
    {
        return DB::transaction(function () use ($definition) {
            if (! $definition->assessmentComponents()->exists()) {
                $definition->delete();

                return true;
            }

            $definition->update(['is_active' => false]);

            return false;
        });
    }

    /**
     * Create an assessment component for a teaching assignment.
     *
     * @throws DomainException
     */
    public function createComponent(
        TeachingAssignment $teachingAssignment,
        AssessmentDefinition $definition,
        ?int $weight = null
    ): AssessmentComponent {
        if ((int) $teachingAssignment->academic_year_id !== (int) $definition->academic_year_id) {
            throw new DomainException('Definisi penilaian tidak sesuai dengan tahun ajaran penugasan mengajar.');
        }

        if (! $definition->is_active) {
            throw new DomainException('Definisi penilaian sudah nonaktif.');
        }

        $existing = AssessmentComponent::where('teaching_assignment_id', $teachingAssignment->id)
            ->where('assessment_definition_id', $definition->id)
            ->exists();

        if ($existing) {
            throw new DomainException('Komponen penilaian untuk penugasan mengajar ini sudah ada.');
        }

        $componentWeight = $weight ?? $definition->weight;
        $this->validateWeight($componentWeight);

        return DB::transaction(function () use ($teachingAssignment, $definition, $componentWeight) {
            return AssessmentComponent::create([
                'teaching_assignment_id' => $teachingAssignment->id,
                'assessment_definition_id' => $definition->id,
                'weight' => $componentWeight,
            ]);
        });
    }

    /**
     * Delete an assessment component.
     *
     * @throws DomainException
     */
    public function deleteComponent(AssessmentComponent $component): bool
    {
        if ($component->studentAssessmentScores()->exists()) {
            throw new DomainException('Komponen penilaian tidak dapat dihapus karena sudah memiliki data nilai santri.');
        }

        return DB::transaction(function () use ($component) {
            return (bool) $component->delete();
        });
    }

    /**
     * Record a single assessment score for a student.
     *
     * @throws DomainException
     */
    public function recordScore(
        AssessmentComponent $component,
        AcademicEnrollment $enrollment,
        float $score,
        ?string $notes = null,
        ?User $gradedBy = null
    ): StudentAssessmentScore {
        $this->validateScore($score);
        $this->validateEnrollmentForComponent($component, $enrollment);

        $existing = StudentAssessmentScore::where('assessment_component_id', $component->id)
            ->where('academic_enrollment_id', $enrollment->id)
            ->first();

        if ($existing) {
            throw new DomainException('Nilai untuk santri pada komponen penilaian ini sudah ada.');
        }

        $graderId = $gradedBy?->id ?? auth()->id();

        return DB::transaction(function () use ($component, $enrollment, $score, $notes, $graderId) {
            return StudentAssessmentScore::create([
                'assessment_component_id' => $component->id,
                'academic_enrollment_id' => $enrollment->id,
                'score' => $score,
                'notes' => $notes,
                'graded_by' => $graderId,
                'graded_at' => now(),
            ]);
        });
    }

    /**
     * Bulk record or update scores for all students in an assessment component.
     *
     * @param  array<int, array{academic_enrollment_id: int, score: float|int|string, notes?: string|null}>  $scores
     * @return Collection<int, StudentAssessmentScore>
     *
     * @throws DomainException
     */
    public function bulkRecordScores(
        AssessmentComponent $component,
        array $scores,
        ?User $gradedBy = null
    ): Collection {
        $graderId = $gradedBy?->id ?? auth()->id();

        return DB::transaction(function () use ($component, $scores, $graderId) {
            $results = collect();

            foreach ($scores as $item) {
                $enrollmentId = $item['academic_enrollment_id'] ?? null;
                $scoreValue = $item['score'] ?? null;
                $notes = $item['notes'] ?? null;

                if (! $enrollmentId || $scoreValue === null || $scoreValue === '') {
                    continue;
                }

                $scoreFloat = (float) $scoreValue;
                $this->validateScore($scoreFloat);

                $enrollment = AcademicEnrollment::findOrFail($enrollmentId);
                $this->validateEnrollmentForComponent($component, $enrollment);

                $record = StudentAssessmentScore::updateOrCreate(
                    [
                        'assessment_component_id' => $component->id,
                        'academic_enrollment_id' => $enrollment->id,
                    ],
                    [
                        'score' => $scoreFloat,
                        'notes' => $notes,
                        'graded_by' => $graderId,
                        'graded_at' => now(),
                    ]
                );

                $results->push($record);
            }

            return $results;
        });
    }

    /**
     * Update an existing score record.
     *
     * @throws DomainException
     */
    public function updateScore(
        StudentAssessmentScore $scoreRecord,
        float $score,
        ?string $notes = null,
        ?User $gradedBy = null
    ): StudentAssessmentScore {
        $this->validateScore($score);

        $graderId = $gradedBy?->id ?? auth()->id();

        return DB::transaction(function () use ($scoreRecord, $score, $notes, $graderId) {
            $scoreRecord->update([
                'score' => $score,
                'notes' => $notes,
                'graded_by' => $graderId,
                'graded_at' => now(),
            ]);

            return $scoreRecord->fresh(['assessmentComponent', 'academicEnrollment.santri', 'grader']);
        });
    }

    /**
     * Validate assessment score range (0.00 to 100.00).
     *
     * @throws DomainException
     */
    protected function validateScore(float $score): void
    {
        if ($score < 0 || $score > 100) {
            throw new DomainException('Nilai harus berada dalam rentang 0 hingga 100.');
        }
    }

    /**
     * Validate assessment weight (0 to 100).
     *
     * @throws DomainException
     */
    protected function validateWeight(int $weight): void
    {
        if ($weight < 0 || $weight > 100) {
            throw new DomainException('Bobot penilaian harus berada dalam rentang 0 hingga 100.');
        }
    }

    /**
     * Validate assessment type.
     *
     * @throws DomainException
     */
    protected function validateType(string $type): void
    {
        if (! in_array($type, AssessmentDefinition::ALLOWED_TYPES, true)) {
            throw new DomainException("Tipe penilaian '{$type}' tidak valid.");
        }
    }

    /**
     * Validate student enrollment belongs to component class, year, and is active.
     *
     * @throws DomainException
     */
    protected function validateEnrollmentForComponent(AssessmentComponent $component, AcademicEnrollment $enrollment): void
    {
        $assignment = $component->teachingAssignment;
        if (! $assignment) {
            throw new DomainException('Komponen penilaian tidak memiliki penugasan mengajar yang valid.');
        }

        if ((int) $enrollment->kelas_id !== (int) $assignment->kelas_id) {
            throw new DomainException('Santri tidak terdaftar di kelas penugasan mengajar ini.');
        }

        if ((int) $enrollment->academic_year_id !== (int) $assignment->academic_year_id) {
            throw new DomainException('Santri tidak terdaftar di tahun ajaran penugasan mengajar ini.');
        }

        if ($enrollment->status !== AcademicEnrollment::STATUS_AKTIF) {
            throw new DomainException('Santri tidak berstatus aktif dalam penempatan akademik ini.');
        }
    }
}
