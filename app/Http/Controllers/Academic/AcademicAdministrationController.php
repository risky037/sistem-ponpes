<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\AcademicEnrollment;
use App\Models\AcademicExportLog;
use App\Models\AcademicPerformanceSummary;
use App\Models\AcademicYear;
use App\Models\AssessmentComponent;
use App\Models\AssessmentDefinition;
use App\Models\AttendanceRecord;
use App\Models\Mapel;
use App\Models\StudentAssessmentScore;
use App\Models\TeachingAssignment;
use App\Models\TeachingSession;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AcademicAdministrationController extends Controller
{
    /**
     * Display the Academic Administration Overview Hub.
     */
    public function index(Request $request): View
    {
        $activeYear = AcademicYear::where('is_active', true)->first();

        $stats = [
            'total_years' => AcademicYear::count(),
            'active_year_name' => $activeYear ? $activeYear->name.' ('.$activeYear->semester.')' : 'Belum Diatur',
            'total_enrollments' => AcademicEnrollment::count(),
            'active_enrollments' => AcademicEnrollment::where('status', AcademicEnrollment::STATUS_AKTIF)->count(),
            'total_mapels' => Mapel::count(),
            'total_assignments' => TeachingAssignment::count(),
            'total_sessions' => TeachingSession::count(),
            'total_attendance_records' => AttendanceRecord::count(),
            'total_definitions' => AssessmentDefinition::count(),
            'total_components' => AssessmentComponent::count(),
            'total_scores' => StudentAssessmentScore::count(),
            'total_summaries' => AcademicPerformanceSummary::count(),
        ];

        $recentLogs = AcademicExportLog::with(['user', 'academicYear', 'kelas'])
            ->orderBy('id', 'desc')
            ->limit(8)
            ->get();

        return view('pages.academic.administration.index', compact('stats', 'activeYear', 'recentLogs'));
    }
}
