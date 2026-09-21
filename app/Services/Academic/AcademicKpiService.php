<?php

namespace App\Services\Academic;

use App\Models\AcademicEnrollment;
use App\Models\AcademicPerformanceSummary;
use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\Kelas;
use App\Models\TeachingAssignment;
use App\Models\TeachingSession;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AcademicKpiService
{
    /**
     * Compute institutional KPIs for an academic year.
     *
     * @return array{
     *     active_enrollments: int,
     *     total_classes: int,
     *     total_teaching_assignments: int,
     *     completed_sessions_count: int,
     *     planned_sessions_count: int,
     *     cancelled_sessions_count: int,
     *     session_fulfillment_rate: float|null,
     *     attendance_rate: float|null,
     *     evaluation_completion_rate: float|null,
     *     average_score: float|null
     * }
     */
    public function getInstitutionalKpis(AcademicYear $year): array
    {
        $activeEnrollmentsCount = AcademicEnrollment::where('academic_year_id', $year->id)
            ->where('status', AcademicEnrollment::STATUS_AKTIF)
            ->count();

        $activeClassesCount = Kelas::whereHas('academic_enrollments', function ($q) use ($year) {
            $q->where('academic_year_id', $year->id)
                ->where('status', AcademicEnrollment::STATUS_AKTIF);
        })->count();

        $teachingAssignmentsCount = TeachingAssignment::where('academic_year_id', $year->id)
            ->where('status', TeachingAssignment::STATUS_AKTIF)
            ->count();

        // Session delivery stats
        $sessionStats = TeachingSession::whereHas('teachingAssignment', function ($q) use ($year) {
            $q->where('academic_year_id', $year->id);
        })->select('status', DB::raw('count(*) as aggregate'))
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $completedSessions = (int) ($sessionStats[TeachingSession::STATUS_COMPLETED] ?? 0);
        $plannedSessions = (int) ($sessionStats[TeachingSession::STATUS_PLANNED] ?? 0);
        $cancelledSessions = (int) ($sessionStats[TeachingSession::STATUS_CANCELLED] ?? 0);
        $deliverableSessions = $completedSessions + $plannedSessions;

        $sessionFulfillmentRate = $deliverableSessions > 0
            ? round(($completedSessions / $deliverableSessions) * 100, 2)
            : null;

        // Attendance stats
        $attendanceStats = AttendanceRecord::whereHas('teachingSession.teachingAssignment', function ($q) use ($year) {
            $q->where('academic_year_id', $year->id);
        })->select('status', DB::raw('count(*) as aggregate'))
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $presentCount = (int) ($attendanceStats[AttendanceRecord::STATUS_HADIR] ?? 0);
        $totalAttendanceRecords = $attendanceStats->sum();

        $attendanceRate = $totalAttendanceRecords > 0
            ? round(($presentCount / $totalAttendanceRecords) * 100, 2)
            : null;

        // Performance summary stats
        $summariesQuery = AcademicPerformanceSummary::whereHas('academicEnrollment', function ($q) use ($year) {
            $q->where('academic_year_id', $year->id)
                ->where('status', AcademicEnrollment::STATUS_AKTIF);
        });

        $completeSummariesCount = (clone $summariesQuery)
            ->where('computation_status', AcademicPerformanceSummary::STATUS_LENGKAP)
            ->count();

        $evaluationCompletionRate = $activeEnrollmentsCount > 0
            ? round(($completeSummariesCount / $activeEnrollmentsCount) * 100, 2)
            : null;

        $rawAvgScore = (clone $summariesQuery)
            ->whereNotNull('average_score')
            ->avg('average_score');

        $averageScore = $rawAvgScore !== null ? round((float) $rawAvgScore, 2) : null;

        return [
            'active_enrollments' => $activeEnrollmentsCount,
            'total_classes' => $activeClassesCount,
            'total_teaching_assignments' => $teachingAssignmentsCount,
            'completed_sessions_count' => $completedSessions,
            'planned_sessions_count' => $plannedSessions,
            'cancelled_sessions_count' => $cancelledSessions,
            'session_fulfillment_rate' => $sessionFulfillmentRate,
            'attendance_rate' => $attendanceRate,
            'evaluation_completion_rate' => $evaluationCompletionRate,
            'average_score' => $averageScore,
        ];
    }

    /**
     * Compute comprehensive attendance analytics: status breakdown, monthly time series, class comparisons, and at-risk indicators.
     *
     * @return array{
     *     status_breakdown: array<string, array{count: int, percentage: float}>,
     *     total_records: int,
     *     monthly_trend: array<int, array{month: string, label: string, rate: float|null, present: int, total: int}>,
     *     class_comparison: array<int, array{kelas_id: int, kelas_name: string, attendance_rate: float|null, present_count: int, total_records: int}>,
     *     at_risk_attendance_count: int
     * }
     */
    public function getAttendanceAnalytics(AcademicYear $year): array
    {
        $records = AttendanceRecord::whereHas('teachingSession.teachingAssignment', function ($q) use ($year) {
            $q->where('academic_year_id', $year->id);
        })->with(['teachingSession:id,session_date', 'academicEnrollment:id,kelas_id'])
            ->get(['id', 'teaching_session_id', 'academic_enrollment_id', 'status']);

        $totalRecords = $records->count();

        $statusCounts = [
            AttendanceRecord::STATUS_HADIR => $records->where('status', AttendanceRecord::STATUS_HADIR)->count(),
            AttendanceRecord::STATUS_IZIN => $records->where('status', AttendanceRecord::STATUS_IZIN)->count(),
            AttendanceRecord::STATUS_SAKIT => $records->where('status', AttendanceRecord::STATUS_SAKIT)->count(),
            AttendanceRecord::STATUS_ALPHA => $records->where('status', AttendanceRecord::STATUS_ALPHA)->count(),
        ];

        $statusBreakdown = [];
        foreach ($statusCounts as $st => $cnt) {
            $statusBreakdown[$st] = [
                'count' => $cnt,
                'percentage' => $totalRecords > 0 ? round(($cnt / $totalRecords) * 100, 1) : 0.0,
            ];
        }

        // Monthly time series grouping
        $monthlyGrouped = $records->groupBy(function ($rec) {
            $date = $rec->teachingSession?->session_date;

            return $date ? Carbon::parse($date)->format('Y-m') : 'unknown';
        })->forget('unknown');

        $monthlyTrend = [];
        foreach ($monthlyGrouped->sortKeys() as $ym => $recs) {
            $tot = $recs->count();
            $pres = $recs->where('status', AttendanceRecord::STATUS_HADIR)->count();
            $rate = $tot > 0 ? round(($pres / $tot) * 100, 1) : null;
            $dt = Carbon::createFromFormat('Y-m', $ym);

            $monthlyTrend[] = [
                'month' => $ym,
                'label' => $dt ? $dt->translatedFormat('M Y') : $ym,
                'rate' => $rate,
                'present' => $pres,
                'total' => $tot,
            ];
        }

        // Class comparison
        $classes = Kelas::orderBy('tingkatan')->orderBy('kelas')->get();
        $classComparison = [];

        foreach ($classes as $kelas) {
            $classRecords = $records->filter(function ($rec) use ($kelas) {
                return (int) $rec->academicEnrollment?->kelas_id === (int) $kelas->id;
            });

            $cTotal = $classRecords->count();
            $cPresent = $classRecords->where('status', AttendanceRecord::STATUS_HADIR)->count();
            $cRate = $cTotal > 0 ? round(($cPresent / $cTotal) * 100, 1) : null;

            $classComparison[] = [
                'kelas_id' => $kelas->id,
                'kelas_name' => $kelas->tingkatan.' - '.$kelas->kelas,
                'attendance_rate' => $cRate,
                'present_count' => $cPresent,
                'total_records' => $cTotal,
            ];
        }

        // At risk active enrollments with attendance rate below 75%
        $atRiskCount = AcademicPerformanceSummary::whereHas('academicEnrollment', function ($q) use ($year) {
            $q->where('academic_year_id', $year->id)
                ->where('status', AcademicEnrollment::STATUS_AKTIF);
        })->whereNotNull('attendance_rate')
            ->where('attendance_rate', '<', 75.0)
            ->count();

        return [
            'status_breakdown' => $statusBreakdown,
            'total_records' => $totalRecords,
            'monthly_trend' => $monthlyTrend,
            'class_comparison' => $classComparison,
            'at_risk_attendance_count' => $atRiskCount,
        ];
    }

    /**
     * Compute flexible grade distribution brackets and completion status distribution.
     * Avoids hardcoded rigid labels; permits customizable thresholds.
     *
     * @param  array<string, array{min: float, max: float}>|null  $customBands
     * @return array{
     *     bands: array<string, array{label: string, count: int, percentage: float}>,
     *     total_scored_students: int,
     *     completion_status_breakdown: array<string, array{count: int, percentage: float}>,
     *     average_score: float|null
     * }
     */
    public function getGradeDistribution(AcademicYear $year, ?array $customBands = null): array
    {
        $defaultBands = [
            'band_a' => ['label' => '90 - 100', 'min' => 90.0, 'max' => 100.0],
            'band_b' => ['label' => '80 - 89.9', 'min' => 80.0, 'max' => 89.99],
            'band_c' => ['label' => '70 - 79.9', 'min' => 70.0, 'max' => 79.99],
            'band_d' => ['label' => '60 - 69.9', 'min' => 60.0, 'max' => 69.99],
            'band_e' => ['label' => '< 60', 'min' => 0.0, 'max' => 59.99],
        ];

        $bandsConfig = $customBands ?? $defaultBands;

        $summaries = AcademicPerformanceSummary::whereHas('academicEnrollment', function ($q) use ($year) {
            $q->where('academic_year_id', $year->id)
                ->where('status', AcademicEnrollment::STATUS_AKTIF);
        })->get(['id', 'average_score', 'computation_status']);

        $scoredSummaries = $summaries->whereNotNull('average_score');
        $totalScored = $scoredSummaries->count();

        $bandsResult = [];
        foreach ($bandsConfig as $key => $config) {
            $min = (float) $config['min'];
            $max = (float) $config['max'];
            $count = $scoredSummaries->filter(function ($s) use ($min, $max) {
                $score = (float) $s->average_score;

                return $score >= $min && $score <= $max;
            })->count();

            $percentage = $totalScored > 0 ? round(($count / $totalScored) * 100, 1) : 0.0;

            $bandsResult[$key] = [
                'label' => $config['label'],
                'count' => $count,
                'percentage' => $percentage,
            ];
        }

        // Completion status breakdown
        $totalSummariesCount = $summaries->count();
        $statusBreakdown = [
            AcademicPerformanceSummary::STATUS_LENGKAP => [
                'count' => $summaries->where('computation_status', AcademicPerformanceSummary::STATUS_LENGKAP)->count(),
                'percentage' => 0.0,
            ],
            AcademicPerformanceSummary::STATUS_SEBAGIAN => [
                'count' => $summaries->where('computation_status', AcademicPerformanceSummary::STATUS_SEBAGIAN)->count(),
                'percentage' => 0.0,
            ],
            AcademicPerformanceSummary::STATUS_KOSONG => [
                'count' => $summaries->where('computation_status', AcademicPerformanceSummary::STATUS_KOSONG)->count(),
                'percentage' => 0.0,
            ],
        ];

        foreach ($statusBreakdown as $stKey => $item) {
            $cnt = $item['count'];
            $statusBreakdown[$stKey]['percentage'] = $totalSummariesCount > 0
                ? round(($cnt / $totalSummariesCount) * 100, 1)
                : 0.0;
        }

        $avgScore = $totalScored > 0 ? round((float) $scoredSummaries->avg('average_score'), 2) : null;

        return [
            'bands' => $bandsResult,
            'total_scored_students' => $totalScored,
            'completion_status_breakdown' => $statusBreakdown,
            'average_score' => $avgScore,
        ];
    }

    /**
     * Compute subject-level performance metrics and component coverage.
     *
     * @return Collection<int, array{
     *     mapel_id: int,
     *     mapel_name: string,
     *     kelas_name: string,
     *     teacher_name: string,
     *     assignment_status: string,
     *     component_count: int,
     *     scored_count: int,
     *     average_score: float|null
     * }>
     */
    public function getSubjectIndicators(AcademicYear $year): Collection
    {
        $assignments = TeachingAssignment::where('academic_year_id', $year->id)
            ->with([
                'mapel:id,name,kelas_id',
                'kelas:id,tingkatan,kelas',
                'user:id,name',
                'assessmentComponents.studentAssessmentScores',
            ])
            ->get();

        return $assignments->map(function ($assignment) {
            $scores = $assignment->assessmentComponents
                ->flatMap->studentAssessmentScores
                ->pluck('score')
                ->filter(fn ($s) => $s !== null);

            $avgScore = $scores->isNotEmpty()
                ? round((float) $scores->avg(), 2)
                : null;

            return [
                'mapel_id' => $assignment->mapel_id,
                'mapel_name' => $assignment->mapel?->name ?? '-',
                'kelas_name' => $assignment->kelas ? $assignment->kelas->tingkatan.' - '.$assignment->kelas->kelas : '-',
                'teacher_name' => $assignment->user?->name ?? 'Belum Ditugaskan',
                'assignment_status' => $assignment->status,
                'component_count' => $assignment->assessmentComponents->count(),
                'scored_count' => $scores->count(),
                'average_score' => $avgScore,
            ];
        });
    }

    /**
     * Compute class operational health diagnostics.
     *
     * @return Collection<int, array{
     *     kelas_id: int,
     *     kelas_name: string,
     *     tingkatan: string,
     *     wali_kelas_name: string,
     *     active_students_count: int,
     *     total_assignments_count: int,
     *     completed_sessions_count: int,
     *     planned_sessions_count: int,
     *     attendance_rate: float|null,
     *     average_score: float|null,
     *     evaluation_completion_rate: float|null
     * }>
     */
    public function getClassOperationalHealth(AcademicYear $year): Collection
    {
        $classes = Kelas::with([
            'wali_kelas_assignments' => function ($q) use ($year) {
                $q->where('academic_year_id', $year->id)->with('user:id,name');
            },
            'academic_enrollments' => function ($q) use ($year) {
                $q->where('academic_year_id', $year->id)
                    ->where('status', AcademicEnrollment::STATUS_AKTIF)
                    ->with('performanceSummary');
            },
            'teaching_assignments' => function ($q) use ($year) {
                $q->where('academic_year_id', $year->id)
                    ->with('teachingSessions:id,teaching_assignment_id,status');
            },
        ])->orderBy('tingkatan')->orderBy('kelas')->get();

        return $classes->map(function ($kelas) {
            $waliAssignment = $kelas->wali_kelas_assignments->first();
            $waliKelasName = $waliAssignment?->user?->name ?? 'Belum Ditentukan';

            $activeEnrollments = $kelas->academic_enrollments;
            $studentsCount = $activeEnrollments->count();

            $assignments = $kelas->teaching_assignments;
            $assignmentsCount = $assignments->count();

            $sessions = $assignments->flatMap->teachingSessions;
            $completedSessions = $sessions->where('status', TeachingSession::STATUS_COMPLETED)->count();
            $plannedSessions = $sessions->where('status', TeachingSession::STATUS_PLANNED)->count();

            // Attendance rate from summaries
            $validAttendanceRates = $activeEnrollments
                ->pluck('performanceSummary.attendance_rate')
                ->filter(fn ($v) => $v !== null);

            $classAttendanceRate = $validAttendanceRates->isNotEmpty()
                ? round((float) $validAttendanceRates->avg(), 1)
                : null;

            // Average score from summaries
            $validScores = $activeEnrollments
                ->pluck('performanceSummary.average_score')
                ->filter(fn ($v) => $v !== null);

            $classAvgScore = $validScores->isNotEmpty()
                ? round((float) $validScores->avg(), 2)
                : null;

            // Evaluation completion
            $completeSummaries = $activeEnrollments->filter(function ($e) {
                return $e->performanceSummary?->computation_status === AcademicPerformanceSummary::STATUS_LENGKAP;
            })->count();

            $evalCompletionRate = $studentsCount > 0
                ? round(($completeSummaries / $studentsCount) * 100, 1)
                : null;

            return [
                'kelas_id' => $kelas->id,
                'kelas_name' => $kelas->tingkatan.' - '.$kelas->kelas,
                'tingkatan' => $kelas->tingkatan,
                'wali_kelas_name' => $waliKelasName,
                'active_students_count' => $studentsCount,
                'total_assignments_count' => $assignmentsCount,
                'completed_sessions_count' => $completedSessions,
                'planned_sessions_count' => $plannedSessions,
                'attendance_rate' => $classAttendanceRate,
                'average_score' => $classAvgScore,
                'evaluation_completion_rate' => $evalCompletionRate,
            ];
        });
    }
}
