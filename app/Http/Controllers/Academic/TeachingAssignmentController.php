<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Services\Academic\TeachingAssignmentService;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Toastr;
use Yajra\DataTables\Facades\DataTables;

class TeachingAssignmentController extends Controller
{
    public function __construct(
        protected TeachingAssignmentService $assignmentService
    ) {}

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = TeachingAssignment::with(['kelas', 'mapel', 'user', 'academic_year']);

            if ($request->filled('academic_year_id')) {
                $query->where('academic_year_id', $request->input('academic_year_id'));
            }

            if ($request->filled('kelas_id')) {
                $query->where('kelas_id', $request->input('kelas_id'));
            }

            $teachers = User::role(['Administrator', 'Pengurus'])->orderBy('name')->get();

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('teacher_name', function ($row) {
                    return e($row->user?->name ?? '-');
                })
                ->addColumn('mapel_name', function ($row) {
                    $code = $row->mapel?->code ? ' <span class="badge bg-light text-dark border">'.e($row->mapel->code).'</span>' : '';

                    return e($row->mapel?->name ?? '-').$code;
                })
                ->addColumn('kelas_name', function ($row) {
                    return e($row->kelas ? $row->kelas->tingkatan.' - '.$row->kelas->kelas : '-');
                })
                ->addColumn('academic_year_name', function ($row) {
                    return e($row->academic_year ? $row->academic_year->name.' ('.$row->academic_year->semester.')' : '-');
                })
                ->editColumn('status', function ($row) {
                    $badgeClass = $row->status === TeachingAssignment::STATUS_AKTIF ? 'bg-success' : 'bg-secondary';

                    return '<span class="badge '.$badgeClass.'">'.e($row->status).'</span>';
                })
                ->editColumn('notes', function ($row) {
                    return e($row->notes ?? '-');
                })
                ->addColumn('action', function ($row) use ($teachers) {
                    return view('pages.academic.teaching_assignment.include.action', [
                        'model' => $row,
                        'teachers' => $teachers,
                    ]);
                })
                ->rawColumns(['mapel_name', 'status', 'action'])
                ->toJson();
        }

        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();
        $activeYear = AcademicYear::where('is_active', true)->first();
        $kelasList = Kelas::orderBy('tingkatan')->orderBy('kelas')->get();
        $mapels = Mapel::active()->with('kelas')->orderBy('name')->get();
        $teachers = User::role(['Administrator', 'Pengurus'])->orderBy('name')->get();

        return view('pages.academic.teaching_assignment.index', [
            'academicYears' => $academicYears,
            'activeYear' => $activeYear,
            'kelasList' => $kelasList,
            'mapels' => $mapels,
            'teachers' => $teachers,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kelas_id' => 'required|exists:kelas,id',
            'mapel_id' => 'required|exists:mapels,id',
            'user_id' => 'required|exists:users,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'notes' => 'nullable|string|max:500',
            'status' => 'nullable|in:'.implode(',', TeachingAssignment::ALLOWED_STATUSES),
        ]);

        try {
            $kelas = Kelas::findOrFail($validated['kelas_id']);
            $mapel = Mapel::findOrFail($validated['mapel_id']);
            $teacher = User::findOrFail($validated['user_id']);
            $academicYear = AcademicYear::findOrFail($validated['academic_year_id']);

            $this->assignmentService->assign(
                kelas: $kelas,
                mapel: $mapel,
                teacher: $teacher,
                academicYear: $academicYear,
                notes: $validated['notes'] ?? null,
                status: $validated['status'] ?? TeachingAssignment::STATUS_AKTIF
            );

            Toastr::success('Berhasil menugaskan pengajar mata pelajaran');

            return redirect()->back();
        } catch (DomainException $e) {
            Toastr::warning($e->getMessage());

            return redirect()->back()->withInput();
        } catch (\Throwable $th) {
            Log::error('TeachingAssignmentController store error: '.$th->getMessage(), [
                'user_id' => auth()->id(),
                'request_uri' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
                'exception' => $th,
            ]);
            Toastr::error('Gagal menugaskan pengajar');

            return redirect()->back()->withInput();
        }
    }

    public function update(Request $request, TeachingAssignment $teachingAssignment)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'status' => 'required|in:'.implode(',', TeachingAssignment::ALLOWED_STATUSES),
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $teacher = User::findOrFail($validated['user_id']);

            $this->assignmentService->updateTeacher(
                assignment: $teachingAssignment,
                newTeacher: $teacher,
                notes: $validated['notes'] ?? null
            );

            $this->assignmentService->updateStatus(
                assignment: $teachingAssignment,
                status: $validated['status'],
                notes: $validated['notes'] ?? null
            );

            Toastr::success('Berhasil memperbarui penugasan mengajar');

            return redirect()->back();
        } catch (DomainException $e) {
            Toastr::warning($e->getMessage());

            return redirect()->back()->withInput();
        } catch (\Throwable $th) {
            Log::error('TeachingAssignmentController update error: '.$th->getMessage(), [
                'user_id' => auth()->id(),
                'request_uri' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
                'exception' => $th,
            ]);
            Toastr::error('Gagal memperbarui penugasan mengajar');

            return redirect()->back()->withInput();
        }
    }

    public function destroy(TeachingAssignment $teachingAssignment)
    {
        try {
            $this->assignmentService->deactivate($teachingAssignment);
            Toastr::success('Berhasil menonaktifkan penugasan mengajar');

            return redirect()->back();
        } catch (\Throwable $th) {
            Log::error('TeachingAssignmentController destroy error: '.$th->getMessage(), [
                'user_id' => auth()->id(),
                'request_uri' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
                'exception' => $th,
            ]);
            Toastr::error('Gagal menonaktifkan penugasan mengajar');

            return redirect()->back();
        }
    }
}
