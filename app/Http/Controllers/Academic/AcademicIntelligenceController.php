<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\AcademicIntelligenceFilterRequest;
use App\Models\AcademicYear;
use App\Models\Kelas;
use App\Services\Academic\AcademicIntelligenceService;
use App\Services\Academic\AcademicKpiService;
use App\Services\Academic\TeacherWorkloadService;
use DomainException;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Toastr;

class AcademicIntelligenceController extends Controller
{
    public function __construct(
        protected AcademicIntelligenceService $intelligenceService,
        protected AcademicKpiService $kpiService,
        protected TeacherWorkloadService $workloadService
    ) {}

    /**
     * Display the Academic Intelligence Dashboard.
     */
    public function index(AcademicIntelligenceFilterRequest $request): View
    {
        $academicYears = AcademicYear::orderBy('name', 'desc')->orderBy('semester', 'asc')->get();
        $classes = Kelas::orderBy('tingkatan')->orderBy('kelas')->get();

        $selectedYearId = $request->input('academic_year_id');
        $year = $selectedYearId
            ? AcademicYear::find($selectedYearId)
            : (AcademicYear::where('is_active', true)->first() ?? AcademicYear::latest('id')->first());

        $dashboardData = null;
        $comparisonData = null;
        $snapshots = collect();

        if ($year) {
            $dashboardData = $this->intelligenceService->getDashboardOverview($year);
            $snapshots = $this->intelligenceService->getHistoricalSnapshots($year);

            if ($request->filled('compare_year_id')) {
                $compareYear = AcademicYear::find($request->input('compare_year_id'));
                if ($compareYear) {
                    $comparisonData = $this->intelligenceService->getHistoricalComparison($year, $compareYear);
                }
            }
        }

        return view('pages.academic.intelligence.index', compact(
            'year',
            'academicYears',
            'classes',
            'dashboardData',
            'comparisonData',
            'snapshots'
        ));
    }

    /**
     * Display the Teacher Workload Intelligence page.
     */
    public function workload(AcademicIntelligenceFilterRequest $request): View
    {
        $academicYears = AcademicYear::orderBy('name', 'desc')->orderBy('semester', 'asc')->get();

        $selectedYearId = $request->input('academic_year_id');
        $year = $selectedYearId
            ? AcademicYear::find($selectedYearId)
            : (AcademicYear::where('is_active', true)->first() ?? AcademicYear::latest('id')->first());

        $workloadOverview = collect();
        $workloadStats = [
            'total_active_teachers' => 0,
            'total_assignments' => 0,
            'total_sessions_completed' => 0,
            'avg_assignments_per_teacher' => 0.0,
            'avg_sessions_per_teacher' => 0.0,
            'overall_fulfillment_rate' => null,
        ];

        if ($year) {
            $workloadOverview = $this->workloadService->getTeacherWorkloadOverview($year);
            $workloadStats = $this->workloadService->getWorkloadDistributionStats($year);
        }

        return view('pages.academic.intelligence.workload', compact(
            'year',
            'academicYears',
            'workloadOverview',
            'workloadStats'
        ));
    }

    /**
     * Display the Subject Indicators & Diagnostics page.
     */
    public function subjectAnalytics(AcademicIntelligenceFilterRequest $request): View
    {
        $academicYears = AcademicYear::orderBy('name', 'desc')->orderBy('semester', 'asc')->get();

        $selectedYearId = $request->input('academic_year_id');
        $year = $selectedYearId
            ? AcademicYear::find($selectedYearId)
            : (AcademicYear::where('is_active', true)->first() ?? AcademicYear::latest('id')->first());

        $subjectIndicators = collect();

        if ($year) {
            $subjectIndicators = $this->kpiService->getSubjectIndicators($year);
        }

        return view('pages.academic.intelligence.subjects', compact(
            'year',
            'academicYears',
            'subjectIndicators'
        ));
    }

    /**
     * Purge cache and recalculate metrics on demand.
     */
    public function refresh(Request $request, AcademicYear $year): RedirectResponse
    {
        try {
            $this->intelligenceService->purgeCache($year);
            $this->intelligenceService->getDashboardOverview($year, true);

            Toastr::success("Metrik intelijen akademik tahun ajaran {$year->name} ({$year->semester}) berhasil disegarkan.", 'Sukses');

            return redirect()->back();
        } catch (Exception $e) {
            Log::error('AcademicIntelligenceController::refresh error: '.$e->getMessage());
            Toastr::error('Terjadi kesalahan saat menyegarkan data intelijen.', 'Error');

            return redirect()->back();
        }
    }

    /**
     * Capture and persist an immutable point-in-time snapshot set.
     */
    public function snapshot(Request $request, AcademicYear $year): RedirectResponse
    {
        try {
            $snapshots = $this->intelligenceService->captureSnapshot($year);

            Toastr::success("Snapshot historis ({$snapshots->count()} domain) berhasil dibekukan dan disimpan.", 'Sukses');

            return redirect()->back();
        } catch (DomainException $e) {
            Toastr::warning($e->getMessage(), 'Peringatan');

            return redirect()->back();
        } catch (Exception $e) {
            Log::error('AcademicIntelligenceController::snapshot error: '.$e->getMessage());
            Toastr::error('Terjadi kesalahan saat membuat snapshot historis.', 'Error');

            return redirect()->back();
        }
    }
}
