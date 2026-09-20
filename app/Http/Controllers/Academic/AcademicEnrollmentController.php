<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\AcademicEnrollment;
use App\Models\AcademicYear;
use App\Models\Kelas;
use App\Models\Santri;
use App\Services\Academic\AcademicEnrollmentService;
use DomainException;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Toastr;
use Yajra\DataTables\Facades\DataTables;

class AcademicEnrollmentController extends Controller
{
    public function __construct(
        protected AcademicEnrollmentService $enrollmentService
    ) {}

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = AcademicEnrollment::with(['santri.user', 'academic_year', 'kelas'])
                ->latest('enrolled_at');

            if ($request->filled('academic_year_id')) {
                $query->where('academic_year_id', $request->input('academic_year_id'));
            }

            $kelasList = Kelas::orderBy('tingkatan')->orderBy('kelas')->get();

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('santri_name', function ($row) {
                    return e($row->santri?->user?->name ?? $row->santri?->nama_lengkap ?? '-');
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
                ->rawColumns(['santri_nis', 'status', 'action'])
                ->toJson();
        }

        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();
        $activeYear = AcademicYear::where('is_active', true)->first();
        $kelasList = Kelas::orderBy('tingkatan')->orderBy('kelas')->get();
        $santris = Santri::where('status', 'Santri Aktif')
            ->with('user')
            ->get()
            ->sortBy('user.name');

        return view('pages.academic.enrollment.index', [
            'academicYears' => $academicYears,
            'activeYear' => $activeYear,
            'kelasList' => $kelasList,
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

            Toastr::success('Berhasil mendaftarkan santri ke kelas akademik');

            return redirect()->back();
        } catch (DomainException $e) {
            Toastr::warning($e->getMessage());

            return redirect()->back()->withInput();
        } catch (Exception $e) {
            Log::error('AcademicEnrollmentController store error: '.$e->getMessage(), [
                'user_id' => auth()->id(),
                'request_uri' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
                'exception' => $e,
            ]);
            Toastr::error('Gagal mendaftarkan santri ke kelas akademik');

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

            Toastr::success('Berhasil memperbarui data pendaftaran akademik');

            return redirect()->back();
        } catch (Exception $e) {
            Log::error('AcademicEnrollmentController update error: '.$e->getMessage(), [
                'user_id' => auth()->id(),
                'request_uri' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
                'exception' => $e,
            ]);
            Toastr::error('Gagal memperbarui data pendaftaran akademik');

            return redirect()->back()->withInput();
        }
    }

    public function destroy(AcademicEnrollment $academicEnrollment)
    {
        try {
            $this->enrollmentService->deactivate($academicEnrollment);
            Toastr::success('Pendaftaran santri berhasil dinonaktifkan (riwayat akademik dipertahankan)');

            return redirect()->back();
        } catch (Exception $e) {
            Log::error('AcademicEnrollmentController destroy error: '.$e->getMessage(), [
                'user_id' => auth()->id(),
                'request_uri' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
                'exception' => $e,
            ]);
            Toastr::error('Gagal menonaktifkan pendaftaran santri');

            return redirect()->back();
        }
    }
}
