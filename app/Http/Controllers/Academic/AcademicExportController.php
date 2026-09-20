<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\AcademicExportLog;
use App\Models\AcademicYear;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\User;
use App\Services\Academic\AcademicExportService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AcademicExportController extends Controller
{
    public function __construct(
        protected AcademicExportService $exportService
    ) {}

    /**
     * Display the Academic Export Center Hub with filter options and recent history.
     */
    public function index(Request $request): View
    {
        $academicYears = AcademicYear::orderBy('id', 'desc')->get();
        $activeYear = AcademicYear::where('is_active', true)->first();
        $kelasList = Kelas::orderBy('tingkatan')->orderBy('kelas')->get();
        $mapels = Mapel::orderBy('name')->get();
        $teachers = User::whereHas('teachingAssignments')->orderBy('name')->get();
        $logs = $this->exportService->getRecentLogs(25);

        return view('pages.academic.export.index', compact(
            'academicYears',
            'activeYear',
            'kelasList',
            'mapels',
            'teachers',
            'logs'
        ));
    }

    /**
     * Export academic enrollments to Excel or printable view.
     */
    public function exportEnrollment(Request $request): BinaryFileResponse|View
    {
        $validated = $request->validate([
            'format' => 'required|in:xlsx,print',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'kelas_id' => 'nullable|exists:kelas,id',
            'status' => 'nullable|string',
        ]);

        $result = $this->exportService->exportEnrollment(
            filters: $validated,
            format: $validated['format'],
            user: $request->user(),
            ip: $request->ip()
        );

        if ($validated['format'] === AcademicExportLog::FORMAT_XLSX) {
            return $result;
        }

        $academicYear = ! empty($validated['academic_year_id']) ? AcademicYear::find($validated['academic_year_id']) : null;
        $kelas = ! empty($validated['kelas_id']) ? Kelas::find($validated['kelas_id']) : null;

        return view('pages.academic.export.print_enrollment', compact('result', 'academicYear', 'kelas'));
    }

    /**
     * Export teaching assignments to Excel or printable view.
     */
    public function exportTeachingAssignment(Request $request): BinaryFileResponse|View
    {
        $validated = $request->validate([
            'format' => 'required|in:xlsx,print',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'kelas_id' => 'nullable|exists:kelas,id',
            'user_id' => 'nullable|exists:users,id',
            'status' => 'nullable|string',
        ]);

        $result = $this->exportService->exportTeachingAssignment(
            filters: $validated,
            format: $validated['format'],
            user: $request->user(),
            ip: $request->ip()
        );

        if ($validated['format'] === AcademicExportLog::FORMAT_XLSX) {
            return $result;
        }

        $academicYear = ! empty($validated['academic_year_id']) ? AcademicYear::find($validated['academic_year_id']) : null;
        $kelas = ! empty($validated['kelas_id']) ? Kelas::find($validated['kelas_id']) : null;

        return view('pages.academic.export.print_teaching_assignment', compact('result', 'academicYear', 'kelas'));
    }

    /**
     * Export attendance summary to Excel or printable view.
     */
    public function exportAttendance(Request $request): BinaryFileResponse|View
    {
        $validated = $request->validate([
            'format' => 'required|in:xlsx,print',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'kelas_id' => 'nullable|exists:kelas,id',
        ]);

        $result = $this->exportService->exportAttendance(
            filters: $validated,
            format: $validated['format'],
            user: $request->user(),
            ip: $request->ip()
        );

        if ($validated['format'] === AcademicExportLog::FORMAT_XLSX) {
            return $result;
        }

        $academicYear = ! empty($validated['academic_year_id']) ? AcademicYear::find($validated['academic_year_id']) : null;
        $kelas = ! empty($validated['kelas_id']) ? Kelas::find($validated['kelas_id']) : null;

        return view('pages.academic.export.print_attendance_summary', compact('result', 'academicYear', 'kelas'));
    }

    /**
     * Export assessment score summary to Excel or printable view.
     */
    public function exportAssessment(Request $request): BinaryFileResponse|View
    {
        $validated = $request->validate([
            'format' => 'required|in:xlsx,print',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'kelas_id' => 'nullable|exists:kelas,id',
            'mapel_id' => 'nullable|exists:mapels,id',
        ]);

        $result = $this->exportService->exportAssessment(
            filters: $validated,
            format: $validated['format'],
            user: $request->user(),
            ip: $request->ip()
        );

        if ($validated['format'] === AcademicExportLog::FORMAT_XLSX) {
            return $result;
        }

        $academicYear = ! empty($validated['academic_year_id']) ? AcademicYear::find($validated['academic_year_id']) : null;
        $kelas = ! empty($validated['kelas_id']) ? Kelas::find($validated['kelas_id']) : null;
        $mapel = ! empty($validated['mapel_id']) ? Mapel::find($validated['mapel_id']) : null;

        return view('pages.academic.export.print_assessment_summary', compact('result', 'academicYear', 'kelas', 'mapel'));
    }

    /**
     * Export performance analytics to Excel or printable view.
     */
    public function exportPerformance(Request $request): BinaryFileResponse|View
    {
        $validated = $request->validate([
            'format' => 'required|in:xlsx,print',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'kelas_id' => 'nullable|exists:kelas,id',
            'computation_status' => 'nullable|string',
        ]);

        $result = $this->exportService->exportPerformance(
            filters: $validated,
            format: $validated['format'],
            user: $request->user(),
            ip: $request->ip()
        );

        if ($validated['format'] === AcademicExportLog::FORMAT_XLSX) {
            return $result;
        }

        $academicYear = ! empty($validated['academic_year_id']) ? AcademicYear::find($validated['academic_year_id']) : null;
        $kelas = ! empty($validated['kelas_id']) ? Kelas::find($validated['kelas_id']) : null;

        return view('pages.academic.export.print_performance', compact('result', 'academicYear', 'kelas'));
    }
}
