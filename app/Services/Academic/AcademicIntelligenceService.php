<?php

namespace App\Services\Academic;

use App\Models\AcademicKpiSnapshot;
use App\Models\AcademicYear;
use App\Models\User;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AcademicIntelligenceService
{
    public const CACHE_TTL_SECONDS = 1800; // 30 minutes

    public function __construct(
        protected AcademicKpiService $kpiService,
        protected TeacherWorkloadService $workloadService
    ) {}

    /**
     * Get complete dashboard overview metrics with in-memory caching.
     *
     * @return array{
     *     kpis: array,
     *     attendance: array,
     *     grades: array,
     *     workload_stats: array,
     *     class_health: Collection,
     *     generated_at: string
     * }
     */
    public function getDashboardOverview(AcademicYear $year, bool $forceFresh = false): array
    {
        $cacheKey = $this->getCacheKey($year);

        if ($forceFresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($year) {
            return [
                'kpis' => $this->kpiService->getInstitutionalKpis($year),
                'attendance' => $this->kpiService->getAttendanceAnalytics($year),
                'grades' => $this->kpiService->getGradeDistribution($year),
                'workload_stats' => $this->workloadService->getWorkloadDistributionStats($year),
                'class_health' => $this->kpiService->getClassOperationalHealth($year),
                'generated_at' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * Capture and persist an immutable point-in-time KPI snapshot set for an academic year.
     *
     * @return Collection<int, AcademicKpiSnapshot>
     *
     * @throws DomainException
     */
    public function captureSnapshot(AcademicYear $year, ?User $capturedBy = null): Collection
    {
        return DB::transaction(function () use ($year, $capturedBy) {
            $today = now()->toDateString();
            $userId = $capturedBy?->id ?? auth()->id();

            // 1. Institutional KPI
            $kpis = $this->kpiService->getInstitutionalKpis($year);
            $s1 = AcademicKpiSnapshot::create([
                'academic_year_id' => $year->id,
                'snapshot_type' => AcademicKpiSnapshot::TYPE_INSTITUTIONAL_KPI,
                'snapshot_date' => $today,
                'metrics' => $kpis,
                'captured_by' => $userId,
            ]);

            // 2. Attendance Analytics
            $attendance = $this->kpiService->getAttendanceAnalytics($year);
            $s2 = AcademicKpiSnapshot::create([
                'academic_year_id' => $year->id,
                'snapshot_type' => AcademicKpiSnapshot::TYPE_ATTENDANCE_ANALYTICS,
                'snapshot_date' => $today,
                'metrics' => $attendance,
                'captured_by' => $userId,
            ]);

            // 3. Grade Distribution
            $grades = $this->kpiService->getGradeDistribution($year);
            $s3 = AcademicKpiSnapshot::create([
                'academic_year_id' => $year->id,
                'snapshot_type' => AcademicKpiSnapshot::TYPE_GRADE_DISTRIBUTION,
                'snapshot_date' => $today,
                'metrics' => $grades,
                'captured_by' => $userId,
            ]);

            // 4. Teacher Workload
            $workload = $this->workloadService->getWorkloadDistributionStats($year);
            $s4 = AcademicKpiSnapshot::create([
                'academic_year_id' => $year->id,
                'snapshot_type' => AcademicKpiSnapshot::TYPE_TEACHER_WORKLOAD,
                'snapshot_date' => $today,
                'metrics' => $workload,
                'captured_by' => $userId,
            ]);

            // 5. Operational Health
            $health = $this->kpiService->getClassOperationalHealth($year)->toArray();
            $s5 = AcademicKpiSnapshot::create([
                'academic_year_id' => $year->id,
                'snapshot_type' => AcademicKpiSnapshot::TYPE_OPERATIONAL_HEALTH,
                'snapshot_date' => $today,
                'metrics' => $health,
                'captured_by' => $userId,
            ]);

            // Invalidate current cache to align
            $this->purgeCache($year);

            return collect([$s1, $s2, $s3, $s4, $s5]);
        });
    }

    /**
     * Get historical snapshots for an academic year.
     *
     * @return Collection<int, AcademicKpiSnapshot>
     */
    public function getHistoricalSnapshots(AcademicYear $year): Collection
    {
        return AcademicKpiSnapshot::forAcademicYear($year)
            ->with('capturedBy:id,name')
            ->orderBy('snapshot_date', 'desc')
            ->orderBy('id', 'desc')
            ->get();
    }

    /**
     * Compare key institutional metrics between two academic years.
     *
     * @return array{
     *     current: array,
     *     previous: array|null,
     *     delta: array|null
     * }
     */
    public function getHistoricalComparison(AcademicYear $currentYear, ?AcademicYear $previousYear = null): array
    {
        $currentKpis = $this->kpiService->getInstitutionalKpis($currentYear);

        if (! $previousYear) {
            return [
                'current' => $currentKpis,
                'previous' => null,
                'delta' => null,
            ];
        }

        $previousKpis = $this->kpiService->getInstitutionalKpis($previousYear);

        $delta = [
            'active_enrollments' => $currentKpis['active_enrollments'] - $previousKpis['active_enrollments'],
            'attendance_rate' => ($currentKpis['attendance_rate'] !== null && $previousKpis['attendance_rate'] !== null)
                ? round($currentKpis['attendance_rate'] - $previousKpis['attendance_rate'], 2)
                : null,
            'average_score' => ($currentKpis['average_score'] !== null && $previousKpis['average_score'] !== null)
                ? round($currentKpis['average_score'] - $previousKpis['average_score'], 2)
                : null,
            'session_fulfillment_rate' => ($currentKpis['session_fulfillment_rate'] !== null && $previousKpis['session_fulfillment_rate'] !== null)
                ? round($currentKpis['session_fulfillment_rate'] - $previousKpis['session_fulfillment_rate'], 2)
                : null,
        ];

        return [
            'current' => $currentKpis,
            'previous' => $previousKpis,
            'delta' => $delta,
        ];
    }

    /**
     * Invalidate intelligence cache for a specific academic year.
     */
    public function purgeCache(AcademicYear $year): void
    {
        Cache::forget($this->getCacheKey($year));
    }

    /**
     * Generate deterministic cache key.
     */
    protected function getCacheKey(AcademicYear $year): string
    {
        return "academic_intelligence_kpi_{$year->id}";
    }
}
