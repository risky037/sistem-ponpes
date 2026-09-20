<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Kelas;
use App\Models\User;
use App\Models\WaliKelasAssignment;
use App\Services\Academic\WaliKelasAssignmentService;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Toastr;
use Yajra\DataTables\Facades\DataTables;

class WaliKelasAssignmentController extends Controller
{
    public function __construct(
        protected WaliKelasAssignmentService $assignmentService
    ) {}

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = WaliKelasAssignment::with(['kelas', 'user', 'academic_year']);

            if ($request->filled('academic_year_id')) {
                $query->where('academic_year_id', $request->input('academic_year_id'));
            }

            $teachers = User::role(['Administrator', 'Pengurus'])->orderBy('name')->get();
            $kelasList = Kelas::orderBy('tingkatan')->orderBy('kelas')->get();

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('teacher_name', function ($row) {
                    return e($row->user?->name ?? '-');
                })
                ->addColumn('kelas_name', function ($row) {
                    return e($row->kelas ? $row->kelas->tingkatan.' - '.$row->kelas->kelas : '-');
                })
                ->addColumn('academic_year_name', function ($row) {
                    return e($row->academic_year ? $row->academic_year->name.' ('.$row->academic_year->semester.')' : '-');
                })
                ->editColumn('notes', function ($row) {
                    return e($row->notes ?? '-');
                })
                ->addColumn('action', function ($row) use ($teachers) {
                    return view('pages.academic.wali_kelas.include.action', [
                        'model' => $row,
                        'teachers' => $teachers,
                    ]);
                })
                ->rawColumns(['action'])
                ->toJson();
        }

        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();
        $activeYear = AcademicYear::where('is_active', true)->first();
        $kelasList = Kelas::orderBy('tingkatan')->orderBy('kelas')->get();
        $teachers = User::role(['Administrator', 'Pengurus'])->orderBy('name')->get();

        return view('pages.academic.wali_kelas.index', [
            'academicYears' => $academicYears,
            'activeYear' => $activeYear,
            'kelasList' => $kelasList,
            'teachers' => $teachers,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kelas_id' => 'required|exists:kelas,id',
            'user_id' => 'required|exists:users,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $kelas = Kelas::findOrFail($validated['kelas_id']);
            $teacher = User::findOrFail($validated['user_id']);
            $academicYear = AcademicYear::findOrFail($validated['academic_year_id']);

            $this->assignmentService->assign(
                kelas: $kelas,
                user: $teacher,
                academicYear: $academicYear,
                notes: $validated['notes'] ?? null
            );

            Toastr::success('Berhasil menugaskan wali kelas');

            return redirect()->back();
        } catch (DomainException $e) {
            Toastr::warning($e->getMessage());

            return redirect()->back()->withInput();
        } catch (\Throwable $th) {
            Log::error('WaliKelasAssignmentController store error: '.$th->getMessage(), [
                'user_id' => auth()->id(),
                'request_uri' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
                'exception' => $th,
            ]);
            Toastr::error('Gagal menugaskan wali kelas');

            return redirect()->back()->withInput();
        }
    }

    public function update(Request $request, WaliKelasAssignment $waliKelasAssignment)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $teacher = User::findOrFail($validated['user_id']);

            $this->assignmentService->update(
                assignment: $waliKelasAssignment,
                newUser: $teacher,
                notes: $validated['notes'] ?? null
            );

            Toastr::success('Berhasil memperbarui penugasan wali kelas');

            return redirect()->back();
        } catch (DomainException $e) {
            Toastr::warning($e->getMessage());

            return redirect()->back()->withInput();
        } catch (\Throwable $th) {
            Log::error('WaliKelasAssignmentController update error: '.$th->getMessage(), [
                'user_id' => auth()->id(),
                'request_uri' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
                'exception' => $th,
            ]);
            Toastr::error('Gagal memperbarui penugasan wali kelas');

            return redirect()->back()->withInput();
        }
    }

    public function destroy(WaliKelasAssignment $waliKelasAssignment)
    {
        try {
            $this->assignmentService->delete($waliKelasAssignment);
            Toastr::success('Berhasil menghapus penugasan wali kelas');

            return redirect()->back();
        } catch (\Throwable $th) {
            Log::error('WaliKelasAssignmentController destroy error: '.$th->getMessage(), [
                'user_id' => auth()->id(),
                'request_uri' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
                'exception' => $th,
            ]);
            Toastr::error('Gagal menghapus penugasan wali kelas');

            return redirect()->back();
        }
    }
}
