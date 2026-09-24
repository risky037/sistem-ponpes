<?php

namespace App\Http\Controllers\Academic;

use App\Helpers\ToastrHelper;
use App\Http\Controllers\Controller;
use App\Models\AcademicEnrollment;
use App\Models\AcademicPerformanceSummary;
use App\Models\AcademicYear;
use App\Models\Kelas;
use App\Services\Academic\AcademicPerformanceService;
use DomainException;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class AcademicPerformanceController extends Controller
{
    public function __construct(
        protected AcademicPerformanceService $performanceService
    ) {}

    /**
     * Display a listing of academic enrollments and their performance aggregation summaries.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = AcademicEnrollment::with([
                'santri.user',
                'kelas',
                'academicYear',
                'performanceSummary',
            ])
                ->select('academic_enrollments.*')
                ->orderBy('academic_enrollments.academic_year_id', 'desc')
                ->orderBy('academic_enrollments.kelas_id', 'asc')
                ->orderBy('academic_enrollments.id', 'asc');

            if ($request->filled('academic_year_id')) {
                $query->where('academic_enrollments.academic_year_id', $request->input('academic_year_id'));
            }

            if ($request->filled('kelas_id')) {
                $query->where('academic_enrollments.kelas_id', $request->input('kelas_id'));
            }

            if ($request->filled('status')) {
                $query->where('academic_enrollments.status', $request->input('status'));
            }

            if ($request->filled('computation_status')) {
                $compStatus = $request->input('computation_status');
                if ($compStatus === 'Belum Dihitung') {
                    $query->doesntHave('performanceSummary');
                } else {
                    $query->whereHas('performanceSummary', function ($q) use ($compStatus) {
                        $q->where('computation_status', $compStatus);
                    });
                }
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('santri_name', function ($row) {
                    return e($row->santri?->nama_lengkap ?? '-');
                })
                ->addColumn('nis', function ($row) {
                    return e($row->santri?->nis ?? $row->santri?->no_induk ?? '-');
                })
                ->addColumn('kelas_name', function ($row) {
                    $kelas = $row->kelas;

                    return e($kelas ? $kelas->tingkatan.' - '.$kelas->kelas : '-');
                })
                ->addColumn('academic_year_name', function ($row) {
                    $ay = $row->academicYear;

                    return e($ay ? $ay->name.' ('.$ay->semester.')' : '-');
                })
                ->addColumn('attendance_rate_formatted', function ($row) {
                    $summary = $row->performanceSummary;
                    if (! $summary || $summary->attendance_rate === null) {
                        return '<span class="text-muted">-</span>';
                    }

                    return '<span class="fw-semibold">'.e($summary->attendance_rate).'%</span>';
                })
                ->addColumn('average_score_formatted', function ($row) {
                    $summary = $row->performanceSummary;
                    if (! $summary || $summary->average_score === null) {
                        return '<span class="text-muted">-</span>';
                    }

                    return '<span class="fw-semibold">'.e(number_format((float) $summary->average_score, 2)).'</span>';
                })
                ->addColumn('status_badge', function ($row) {
                    $summary = $row->performanceSummary;
                    if (! $summary) {
                        return '<span class="badge bg-light text-dark border">Belum Dihitung</span>';
                    }

                    return match ($summary->computation_status) {
                        AcademicPerformanceSummary::STATUS_LENGKAP => '<span class="badge bg-success">Lengkap</span>',
                        AcademicPerformanceSummary::STATUS_SEBAGIAN => '<span class="badge bg-warning text-dark">Sebagian</span>',
                        default => '<span class="badge bg-secondary">Kosong</span>',
                    };
                })
                ->addColumn('actions', function ($row) {
                    $showUrl = route('academic.performance.show', $row->id);
                    $generateUrl = route('academic.performance.generate', $row->id);
                    $csrf = csrf_token();

                    return '
                        <div class="btn-group btn-group-sm" role="group">
                            <a href="'.$showUrl.'" class="btn btn-outline-info" title="Detail Performa">
                                <i class="bx bx-show"></i>
                            </a>
                            <form action="'.$generateUrl.'" method="POST" class="d-inline" onsubmit="return confirm(\'Hitung ulang rekap performa santri ini?\')">
                                <input type="hidden" name="_token" value="'.$csrf.'">
                                <button type="submit" class="btn btn-outline-primary" title="Hitung Ulang">
                                    <i class="bx bx-calculator"></i>
                                </button>
                            </form>
                        </div>
                    ';
                })
                ->filterColumn('santri_name', function ($query, $keyword) {
                    $query->whereHas('santri.user', function ($q) use ($keyword) {
                        $q->where('users.name', 'like', "%{$keyword}%");
                    });
                })
                ->orderColumn('santri_name', function ($query, $direction) {
                    $query->join('santris', 'santris.id', '=', 'academic_enrollments.santri_id')
                        ->join('users', 'users.id', '=', 'santris.user_id')
                        ->orderBy('users.name', $direction)
                        ->select('academic_enrollments.*');
                })
                ->filterColumn('kelas_name', function ($query, $keyword) {
                    $query->whereHas('kelas', function ($q) use ($keyword) {
                        $q->where('kelas.kelas', 'like', "%{$keyword}%")
                            ->orWhere('kelas.tingkatan', 'like', "%{$keyword}%");
                    });
                })
                ->orderColumn('kelas_name', function ($query, $direction) {
                    $query->join('kelas', 'kelas.id', '=', 'academic_enrollments.kelas_id')
                        ->orderBy('kelas.tingkatan', $direction)
                        ->orderBy('kelas.kelas', $direction)
                        ->select('academic_enrollments.*');
                })
                ->filterColumn('academic_year_name', function ($query, $keyword) {
                    $query->whereHas('academicYear', function ($q) use ($keyword) {
                        $q->where('academic_years.name', 'like', "%{$keyword}%");
                    });
                })
                ->orderColumn('academic_year_name', function ($query, $direction) {
                    $query->join('academic_years', 'academic_years.id', '=', 'academic_enrollments.academic_year_id')
                        ->orderBy('academic_years.name', $direction)
                        ->select('academic_enrollments.*');
                })
                ->rawColumns(['attendance_rate_formatted', 'average_score_formatted', 'status_badge', 'actions'])
                ->make(true);
        }

        $academicYears = AcademicYear::orderBy('name', 'desc')->orderBy('semester', 'asc')->get();
        $activeYear = AcademicYear::where('is_active', true)->first();
        $kelasList = Kelas::orderBy('tingkatan')->orderBy('kelas')->get();

        return view('pages.academic.performance.index', compact('academicYears', 'activeYear', 'kelasList'));
    }

    /**
     * Display performance breakdown details for an academic enrollment.
     */
    public function show(AcademicEnrollment $enrollment)
    {
        $enrollment->load([
            'santri',
            'kelas',
            'academicYear',
            'performanceSummary',
            'attendanceRecords.teachingSession.teachingAssignment.mapel',
            'attendanceRecords.teachingSession.teachingAssignment.user',
            'studentAssessmentScores.assessmentComponent.assessmentDefinition',
            'studentAssessmentScores.assessmentComponent.teachingAssignment.mapel',
            'studentAssessmentScores.grader',
        ]);

        return view('pages.academic.performance.show', compact('enrollment'));
    }

    /**
     * Generate or regenerate summary for a single enrollment.
     */
    public function store(AcademicEnrollment $enrollment)
    {
        try {
            $this->performanceService->generateSummary($enrollment);
            ToastrHelper::success('Rekap performa akademik santri berhasil dihitung.', 'Sukses');

            return redirect()->back();
        } catch (DomainException $e) {
            ToastrHelper::warning($e->getMessage(), 'Peringatan');

            return redirect()->back();
        } catch (Exception $e) {
            Log::error('AcademicPerformanceController::store error: '.$e->getMessage());
            ToastrHelper::error('Terjadi kesalahan saat menghitung performa akademik.', 'Error');

            return redirect()->back();
        }
    }

    /**
     * Bulk generate or regenerate summaries for all active enrollments in a given academic year.
     */
    public function bulkGenerate(AcademicYear $year)
    {
        try {
            $summaries = $this->performanceService->generateForYear($year);
            ToastrHelper::success("Rekap performa untuk {$summaries->count()} santri aktif berhasil dihitung.", 'Sukses');

            return redirect()->back();
        } catch (DomainException $e) {
            ToastrHelper::warning($e->getMessage(), 'Peringatan');

            return redirect()->back();
        } catch (Exception $e) {
            Log::error('AcademicPerformanceController::bulkGenerate error: '.$e->getMessage());
            ToastrHelper::error('Terjadi kesalahan saat menghitung agregasi tahun ajaran.', 'Error');

            return redirect()->back();
        }
    }
}
