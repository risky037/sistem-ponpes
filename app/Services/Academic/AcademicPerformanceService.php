<?php

namespace App\Services\Academic;

use App\Models\AcademicEnrollment;
use App\Models\AcademicPerformanceSummary;
use App\Models\AcademicYear;
use App\Models\AssessmentComponent;
use App\Models\AttendanceRecord;
use App\Models\StudentAssessmentScore;
use App\Models\TeachingAssignment;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AcademicPerformanceService
{
    /**
     * Generate or regenerate an academic performance summary for an academic enrollment.
     *
     * @throws DomainException
     */
    public function generateSummary(AcademicEnrollment $enrollment): AcademicPerformanceSummary
    {
        $this->validateEnrollmentAnchor($enrollment);

        $attendance = $this->computeAttendance($enrollment);
        $assessment = $this->computeAssessment($enrollment);
        $status = $this->deriveStatus($attendance, $assessment);

        return DB::transaction(function () use ($enrollment, $attendance, $assessment, $status) {
            return AcademicPerformanceSummary::updateOrCreate(
                ['academic_enrollment_id' => $enrollment->id],
                [
                    'total_sessions' => $attendance['total_sessions'],
                    'present_count' => $attendance['present_count'],
                    'excused_count' => $attendance['excused_count'],
                    'sick_count' => $attendance['sick_count'],
                    'absent_count' => $attendance['absent_count'],
                    'attendance_rate' => $attendance['attendance_rate'],
                    'scored_components' => $assessment['scored_components'],
                    'total_components' => $assessment['total_components'],
                    'weighted_score_sum' => $assessment['weighted_score_sum'],
                    'total_weight' => $assessment['total_weight'],
                    'average_score' => $assessment['average_score'],
                    'computation_status' => $status,
                    'source_version' => AcademicPerformanceSummary::CURRENT_SOURCE_VERSION,
                    'computed_at' => now(),
                ]
            );
        });
    }

    /**
     * Bulk generate or regenerate summaries for all active enrollments in a given academic year.
     * Inactive enrollments are strictly skipped.
     *
     * @return Collection<int, AcademicPerformanceSummary>
     */
    public function generateForYear(AcademicYear $year): Collection
    {
        return DB::transaction(function () use ($year) {
            $activeEnrollments = AcademicEnrollment::where('academic_year_id', $year->id)
                ->where('status', AcademicEnrollment::STATUS_AKTIF)
                ->get();

            $summaries = collect();

            foreach ($activeEnrollments as $enrollment) {
                $summaries->push($this->generateSummary($enrollment));
            }

            return $summaries;
        });
    }

    /**
     * Compute attendance metrics for an enrollment.
     *
     * @return array{
     *     total_sessions: int,
     *     present_count: int,
     *     excused_count: int,
     *     sick_count: int,
     *     absent_count: int,
     *     attendance_rate: float|null
     * }
     */
    public function computeAttendance(AcademicEnrollment $enrollment): array
    {
        $records = AttendanceRecord::where('academic_enrollment_id', $enrollment->id)->get();

        $totalSessions = $records->count();
        $presentCount = $records->where('status', AttendanceRecord::STATUS_HADIR)->count();
        $excusedCount = $records->where('status', AttendanceRecord::STATUS_IZIN)->count();
        $sickCount = $records->where('status', AttendanceRecord::STATUS_SAKIT)->count();
        $absentCount = $records->where('status', AttendanceRecord::STATUS_ALPHA)->count();

        $attendanceRate = $totalSessions > 0
            ? round(($presentCount / $totalSessions) * 100, 2)
            : null;

        return [
            'total_sessions' => $totalSessions,
            'present_count' => $presentCount,
            'excused_count' => $excusedCount,
            'sick_count' => $sickCount,
            'absent_count' => $absentCount,
            'attendance_rate' => $attendanceRate,
        ];
    }

    /**
     * Dynamically compute assessment metrics for an enrollment.
     * Recalculates total_components based on active class components and recorded scores.
     *
     * @return array{
     *     scored_components: int,
     *     total_components: int,
     *     weighted_score_sum: float|null,
     *     total_weight: int,
     *     average_score: float|null
     * }
     */
    public function computeAssessment(AcademicEnrollment $enrollment): array
    {
        // 1. Dynamic query for all active teaching assignments and their active assessment components for this class & year
        $assignmentIds = TeachingAssignment::where('kelas_id', $enrollment->kelas_id)
            ->where('academic_year_id', $enrollment->academic_year_id)
            ->where('status', TeachingAssignment::STATUS_AKTIF)
            ->pluck('id');

        $activeComponents = AssessmentComponent::whereIn('teaching_assignment_id', $assignmentIds)
            ->whereHas('assessmentDefinition', function ($q) {
                $q->where('is_active', true);
            })
            ->get();

        // 2. Query all student assessment scores for this enrollment
        $scores = StudentAssessmentScore::where('academic_enrollment_id', $enrollment->id)
            ->with(['assessmentComponent.teachingAssignment'])
            ->get();

        $scoredComponents = 0;
        $weightedScoreSum = 0.0;
        $totalWeight = 0;

        foreach ($scores as $scoreRecord) {
            $component = $scoreRecord->assessmentComponent;
            if (! $component) {
                continue;
            }

            $assignment = $component->teachingAssignment;
            // Verify component belongs to this class and academic year if assignment is linked
            if (! $assignment || ((int) $assignment->kelas_id === (int) $enrollment->kelas_id && (int) $assignment->academic_year_id === (int) $enrollment->academic_year_id)) {
                $weight = (int) $component->weight;
                $scoreVal = (float) $scoreRecord->score;
                $weightedScoreSum += ($scoreVal * $weight);
                $totalWeight += $weight;
                $scoredComponents++;
            }
        }

        // Dynamically compute total components required
        $totalComponents = max($activeComponents->count(), $scoredComponents);

        $averageScore = $totalWeight > 0
            ? round($weightedScoreSum / $totalWeight, 2)
            : null;

        $finalWeightedSum = $totalWeight > 0 ? round($weightedScoreSum, 2) : null;

        return [
            'scored_components' => $scoredComponents,
            'total_components' => $totalComponents,
            'weighted_score_sum' => $finalWeightedSum,
            'total_weight' => $totalWeight,
            'average_score' => $averageScore,
        ];
    }

    /**
     * Derive computation status from attendance and assessment metrics.
     */
    public function deriveStatus(array $attendance, array $assessment): string
    {
        $totalSessions = (int) ($attendance['total_sessions'] ?? 0);
        $attendanceRate = $attendance['attendance_rate'] ?? null;
        $scoredComponents = (int) ($assessment['scored_components'] ?? 0);
        $totalComponents = (int) ($assessment['total_components'] ?? 0);

        // Kosong: no attendance sessions and no assessment scores recorded
        if ($totalSessions === 0 && $scoredComponents === 0) {
            return AcademicPerformanceSummary::STATUS_KOSONG;
        }

        // Lengkap: all components scored, sessions exist, and attendance rate >= 75%
        $hasCompleteComponents = $totalComponents > 0 && $scoredComponents >= $totalComponents;
        $hasSufficientAttendance = $totalSessions > 0 && $attendanceRate !== null && $attendanceRate >= 75.0;

        if ($hasCompleteComponents && $hasSufficientAttendance) {
            return AcademicPerformanceSummary::STATUS_LENGKAP;
        }

        // Sebagian: partial scores or attendance below threshold
        return AcademicPerformanceSummary::STATUS_SEBAGIAN;
    }

    /**
     * Validate enrollment anchor integrity.
     *
     * @throws DomainException
     */
    protected function validateEnrollmentAnchor(AcademicEnrollment $enrollment): void
    {
        if (! $enrollment->exists || ! $enrollment->academic_year_id || ! $enrollment->kelas_id) {
            throw new DomainException('Penempatan akademik tidak memiliki data tahun ajaran atau kelas yang valid.');
        }
    }
}
