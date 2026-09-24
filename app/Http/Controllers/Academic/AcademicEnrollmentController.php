<?php

namespace App\Http\Controllers\Academic;

use App\Helpers\ToastrHelper;
use App\Http\Controllers\Controller;
use App\Models\AcademicEnrollment;
use App\Models\AcademicYear;
use App\Models\Kelas;
use App\Models\Santri;
use App\Models\StudentBatch;
use App\Services\Academic\AcademicEnrollmentService;
use DomainException;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class AcademicEnrollmentController extends Controller
{
    public function __construct(
        protected AcademicEnrollmentService $enrollmentService
    ) {}

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = AcademicEnrollment::with(['santri.user', 'santri.student_batch', 'academic_year', 'kelas'])
                ->select('academic_enrollments.*')
                ->latest('academic_enrollments.enrolled_at');

            if ($request->filled('academic_year_id')) {
                $query->where('academic_enrollments.academic_year_id', $request->input('academic_year_id'));
            }

            if ($request->filled('student_batch_id')) {
                $query->whereHas('santri', function ($q) use ($request) {
                    $q->where('santris.student_batch_id', $request->input('student_batch_id'));
                });
            }

            if ($request->filled('kelas_id')) {
                $query->where('academic_enrollments.kelas_id', $request->input('kelas_id'));
            }

            if ($request->filled('status')) {
                $query->where('academic_enrollments.status', $request->input('status'));
            }

            $kelasList = Kelas::orderBy('tingkatan')->orderBy('kelas')->get();

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('santri_name', function ($row) {
                    return e($row->santri?->user?->name ?? '-');
                })
                ->addColumn('santri_nis', function ($row) {
                    return '<span class="badge bg-light text-dark border">'.e($row->santri?->no_induk ?? '-').'</span>';
                })
                ->addColumn('academic_year_name', function ($row) {
                    return e($row->academic_year ? $row->academic_year->name.' ('.$row->academic_year->semester.')' : '-');
                })
                ->addColumn('kelas_name', function ($row) {
                    return e($row->kelas ? $row->kelas->tingkatan.' - '.$row->kelas->kelas : '-');
                })
                ->editColumn('status', function ($row) {
                    $badgeClass = match ($row->status) {
                        AcademicEnrollment::STATUS_AKTIF => 'bg-success',
                        AcademicEnrollment::STATUS_NONAKTIF => 'bg-secondary',
                        AcademicEnrollment::STATUS_LULUS => 'bg-primary',
                        AcademicEnrollment::STATUS_PINDAH => 'bg-warning text-dark',
                        default => 'bg-secondary',
                    };

                    return '<span class="badge '.$badgeClass.'">'.e($row->status).'</span>';
                })
                ->editColumn('enrolled_at', function ($row) {
                    return $row->enrolled_at?->format('d/m/Y') ?? '-';
                })
                ->addColumn('action', function ($row) use ($kelasList) {
                    return view('pages.academic.enrollment.include.action', [
                        'model' => $row,
                        'kelasList' => $kelasList,
                    ]);
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
                ->filterColumn('santri_nis', function ($query, $keyword) {
                    $query->whereHas('santri', function ($q) use ($keyword) {
                        $q->where('santris.no_induk', 'like', "%{$keyword}%");
                    });
                })
                ->filterColumn('academic_year_name', function ($query, $keyword) {
                    $query->whereHas('academic_year', function ($q) use ($keyword) {
                        $q->where('academic_years.name', 'like', "%{$keyword}%");
                    });
                })
                ->orderColumn('academic_year_name', function ($query, $direction) {
                    $query->join('academic_years', 'academic_years.id', '=', 'academic_enrollments.academic_year_id')
                        ->orderBy('academic_years.name', $direction)
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
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && ! empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->whereHas('santri.user', function ($sq) use ($search) {
                                $sq->where('users.name', 'like', "%{$search}%");
                            })->orWhereHas('santri', function ($sq) use ($search) {
                                $sq->where('santris.no_induk', 'like', "%{$search}%");
                            })->orWhereHas('kelas', function ($sq) use ($search) {
                                $sq->where('kelas.kelas', 'like', "%{$search}%")
                                    ->orWhere('kelas.tingkatan', 'like', "%{$search}%");
                            })->orWhereHas('academic_year', function ($sq) use ($search) {
                                $sq->where('academic_years.name', 'like', "%{$search}%");
                            })->orWhere('academic_enrollments.status', 'like', "%{$search}%");
                        });
                    }
                })
                ->rawColumns(['santri_nis', 'status', 'action'])
                ->toJson();
        }

        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();
        $activeYear = AcademicYear::where('is_active', true)->first();
        $kelasList = Kelas::orderBy('tingkatan')->orderBy('kelas')->get();
        $studentBatches = StudentBatch::orderBy('year', 'desc')->get();
        $santris = Santri::where('status', 'Santri Aktif')
            ->with('user')
            ->get()
            ->sortBy('user.name');

        return view('pages.academic.enrollment.index', [
            'academicYears' => $academicYears,
            'activeYear' => $activeYear,
            'kelasList' => $kelasList,
            'studentBatches' => $studentBatches,
            'santris' => $santris,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'santri_id' => 'required|exists:santris,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'kelas_id' => 'required|exists:kelas,id',
            'status' => 'nullable|in:'.implode(',', AcademicEnrollment::ALLOWED_STATUSES),
            'enrolled_at' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $santri = Santri::findOrFail($validated['santri_id']);
            $academicYear = AcademicYear::findOrFail($validated['academic_year_id']);
            $kelas = Kelas::findOrFail($validated['kelas_id']);

            $this->enrollmentService->enroll(
                santri: $santri,
                academicYear: $academicYear,
                kelas: $kelas,
                notes: $validated['notes'] ?? null,
                enrolledAt: $validated['enrolled_at'] ?? null,
                status: $validated['status'] ?? AcademicEnrollment::STATUS_AKTIF
            );

            ToastrHelper::success('Berhasil mendaftarkan santri ke kelas akademik');

            return redirect()->back();
        } catch (DomainException $e) {
            ToastrHelper::warning($e->getMessage());

            return redirect()->back()->withInput();
        } catch (Exception $e) {
            Log::error('AcademicEnrollmentController store error: '.$e->getMessage(), [
                'user_id' => auth()->id(),
                'request_uri' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
                'exception' => $e,
            ]);
            ToastrHelper::error('Gagal mendaftarkan santri ke kelas akademik');

            return redirect()->back()->withInput();
        }
    }

    public function update(Request $request, AcademicEnrollment $academicEnrollment)
    {
        $validated = $request->validate([
            'kelas_id' => 'required|exists:kelas,id',
            'status' => 'required|in:'.implode(',', AcademicEnrollment::ALLOWED_STATUSES),
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $kelas = Kelas::findOrFail($validated['kelas_id']);

            if ($academicEnrollment->kelas_id !== $kelas->id) {
                $this->enrollmentService->updateKelas($academicEnrollment, $kelas, $validated['notes'] ?? null);
            }

            $this->enrollmentService->updateStatus($academicEnrollment, $validated['status'], $validated['notes'] ?? null);

            ToastrHelper::success('Berhasil memperbarui data pendaftaran akademik');

            return redirect()->back();
        } catch (Exception $e) {
            Log::error('AcademicEnrollmentController update error: '.$e->getMessage(), [
                'user_id' => auth()->id(),
                'request_uri' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
                'exception' => $e,
            ]);
            ToastrHelper::error('Gagal memperbarui data pendaftaran akademik');

            return redirect()->back()->withInput();
        }
    }

    public function destroy(AcademicEnrollment $academicEnrollment)
    {
        try {
            $this->enrollmentService->deactivate($academicEnrollment);
            ToastrHelper::success('Pendaftaran santri berhasil dinonaktifkan (riwayat akademik dipertahankan)');

            return redirect()->back();
        } catch (Exception $e) {
            Log::error('AcademicEnrollmentController destroy error: '.$e->getMessage(), [
                'user_id' => auth()->id(),
                'request_uri' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
                'exception' => $e,
            ]);
            ToastrHelper::error('Gagal menonaktifkan pendaftaran santri');

            return redirect()->back();
        }
    }
}
