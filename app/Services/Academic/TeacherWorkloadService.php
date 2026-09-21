<?php

namespace App\Services\Academic;

use App\Models\AcademicYear;
use App\Models\TeachingAssignment;
use App\Models\TeachingSession;
use Illuminate\Support\Collection;

class TeacherWorkloadService
{
    /**
     * Compute comprehensive teacher workload metrics for all teachers in an academic year.
     *
     * @return Collection<int, array{
     *     user_id: int,
     *     name: string,
     *     email: string,
     *     assignments_count: int,
     *     subjects_count: int,
     *     classes_count: int,
     *     weekly_schedule_slots: int,
     *     planned_sessions: int,
     *     completed_sessions: int,
     *     cancelled_sessions: int,
     *     total_sessions: int,
     *     fulfillment_rate: float|null,
     *     components_count: int,
     *     scored_records_count: int
     * }>
     */
    public function getTeacherWorkloadOverview(AcademicYear $year): Collection
    {
        $assignments = TeachingAssignment::where('academic_year_id', $year->id)
            ->with([
                'user:id,name,email',
                'mapel:id,name',
                'kelas:id,tingkatan,kelas',
                'classSchedules:id,teaching_assignment_id',
                'teachingSessions:id,teaching_assignment_id,status',
                'assessmentComponents.studentAssessmentScores:id,assessment_component_id',
            ])
            ->get();

        $groupedByTeacher = $assignments->groupBy('user_id');

        return $groupedByTeacher->map(function ($teacherAssignments, $userId) {
            $user = $teacherAssignments->first()?->user;

            $distinctSubjectsCount = $teacherAssignments->pluck('mapel_id')->unique()->count();
            $distinctClassesCount = $teacherAssignments->pluck('kelas_id')->unique()->count();
            $weeklySlots = $teacherAssignments->flatMap->classSchedules->count();

            $sessions = $teacherAssignments->flatMap->teachingSessions;
            $completed = $sessions->where('status', TeachingSession::STATUS_COMPLETED)->count();
            $planned = $sessions->where('status', TeachingSession::STATUS_PLANNED)->count();
            $cancelled = $sessions->where('status', TeachingSession::STATUS_CANCELLED)->count();
            $totalActiveSessions = $completed + $planned;

            $fulfillmentRate = $totalActiveSessions > 0
                ? round(($completed / $totalActiveSessions) * 100, 1)
                : null;

            $components = $teacherAssignments->flatMap->assessmentComponents;
            $componentsCount = $components->count();
            $scoredCount = $components->flatMap->studentAssessmentScores->count();

            return [
                'user_id' => (int) $userId,
                'name' => $user?->name ?? 'Pengajar Tidak Ditemukan',
                'email' => $user?->email ?? '-',
                'assignments_count' => $teacherAssignments->count(),
                'subjects_count' => $distinctSubjectsCount,
                'classes_count' => $distinctClassesCount,
                'weekly_schedule_slots' => $weeklySlots,
                'planned_sessions' => $planned,
                'completed_sessions' => $completed,
                'cancelled_sessions' => $cancelled,
                'total_sessions' => $totalActiveSessions,
                'fulfillment_rate' => $fulfillmentRate,
                'components_count' => $componentsCount,
                'scored_records_count' => $scoredCount,
            ];
        })->values();
    }

    /**
     * Compute macro workload distribution statistics for institutional health.
     *
     * @return array{
     *     total_active_teachers: int,
     *     total_assignments: int,
     *     total_sessions_completed: int,
     *     avg_assignments_per_teacher: float,
     *     avg_sessions_per_teacher: float,
     *     overall_fulfillment_rate: float|null
     * }
     */
    public function getWorkloadDistributionStats(AcademicYear $year): array
    {
        $overview = $this->getTeacherWorkloadOverview($year);

        $totalTeachers = $overview->count();
        $totalAssignments = (int) $overview->sum('assignments_count');
        $totalCompleted = (int) $overview->sum('completed_sessions');
        $totalSessions = (int) $overview->sum('total_sessions');

        $avgAssignments = $totalTeachers > 0 ? round($totalAssignments / $totalTeachers, 1) : 0.0;
        $avgSessions = $totalTeachers > 0 ? round($totalCompleted / $totalTeachers, 1) : 0.0;
        $overallFulfillment = $totalSessions > 0 ? round(($totalCompleted / $totalSessions) * 100, 1) : null;

        return [
            'total_active_teachers' => $totalTeachers,
            'total_assignments' => $totalAssignments,
            'total_sessions_completed' => $totalCompleted,
            'avg_assignments_per_teacher' => $avgAssignments,
            'avg_sessions_per_teacher' => $avgSessions,
            'overall_fulfillment_rate' => $overallFulfillment,
        ];
    }
}
