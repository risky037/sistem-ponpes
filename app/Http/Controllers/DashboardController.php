<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\Santri;
use App\Models\StudentAssessmentScore;
use App\Models\TeachingAssignment;
use App\Models\TeachingSession;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $user = $request->user();

        // 1. Santri Role Portal
        if ($user && $user->hasRole('Santri')) {
            $santri = $user->santri()->with([
                'user',
                'wali_santri',
                'kamar_santri.kamar',
                'kelas_santri.kelas',
                'student_batch',
                'academic_enrollments.academicYear',
                'academic_enrollments.kelas',
            ])->first();

            $activeYear = AcademicYear::where('is_active', true)->first();
            $activeEnrollment = null;
            $scores = collect();
            $attendanceStats = [
                'total' => 0,
                'hadir' => 0,
                'izin' => 0,
                'sakit' => 0,
                'alpha' => 0,
                'percentage' => 0,
            ];

            if ($santri) {
                if ($activeYear) {
                    $activeEnrollment = $santri->academic_enrollments()
                        ->where('academic_year_id', $activeYear->id)
                        ->first();
                }

                if (! $activeEnrollment) {
                    $activeEnrollment = $santri->academic_enrollments()->latest('id')->first();
                }

                if ($activeEnrollment) {
                    $records = AttendanceRecord::where('academic_enrollment_id', $activeEnrollment->id)->get();
                    $hadir = $records->where('status', AttendanceRecord::STATUS_HADIR)->count();
                    $izin = $records->where('status', AttendanceRecord::STATUS_IZIN)->count();
                    $sakit = $records->where('status', AttendanceRecord::STATUS_SAKIT)->count();
                    $alpha = $records->where('status', AttendanceRecord::STATUS_ALPHA)->count();
                    $total = $records->count();
                    $percentage = $total > 0 ? round(($hadir / $total) * 100, 1) : 0;

                    $attendanceStats = [
                        'total' => $total,
                        'hadir' => $hadir,
                        'izin' => $izin,
                        'sakit' => $sakit,
                        'alpha' => $alpha,
                        'percentage' => $percentage,
                    ];

                    $scores = StudentAssessmentScore::with([
                        'assessmentComponent.teachingAssignment.mapel',
                        'assessmentComponent.assessmentDefinition',
                    ])
                        ->where('academic_enrollment_id', $activeEnrollment->id)
                        ->get();
                }
            }

            return view('pages.portal.santri', compact('santri', 'activeEnrollment', 'attendanceStats', 'scores'));
        }

        // 2. Guru Role Portal (when not Administrator or Pengurus)
        if ($user && $user->hasRole('Guru') && ! $user->hasAnyRole(['Administrator', 'Pengurus'])) {
            $activeYear = AcademicYear::where('is_active', true)->first();

            $assignmentsQuery = TeachingAssignment::with(['kelas', 'mapel', 'classSchedules'])
                ->where('user_id', $user->id);

            if ($activeYear) {
                $assignmentsQuery->where('academic_year_id', $activeYear->id);
            }

            $assignments = $assignmentsQuery->get();

            $totalSessions = TeachingSession::whereIn('teaching_assignment_id', $assignments->pluck('id'))->count();

            return view('pages.portal.guru', compact('activeYear', 'assignments', 'totalSessions'));
        }

        // 3. Administrator, Pengurus, Keuangan, and Other Default Dashboard
        $santri = new Santri;
        $putri = $santri->where('jenis_kelamin', 'Perempuan')->where('status', 'Santri Aktif')->count();
        $putra = $santri->where('jenis_kelamin', 'Laki-Laki')->where('status', 'Santri Aktif')->count();
        $santri_aktif = $santri->where('status', 'Santri Aktif')->count();
        $santri_alumni = $santri->where('status', 'Santri Alumni')->count();
        $pengurus = User::role('Pengurus')->count();
        $total_santri = Santri::count();

        return view('pages.dashboard', compact('putri', 'putra', 'santri_aktif', 'santri_alumni', 'pengurus', 'total_santri'));
    }
}
