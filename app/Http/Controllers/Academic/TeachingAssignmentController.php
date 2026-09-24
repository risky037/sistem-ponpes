<?php

namespace App\Http\Controllers\Academic;

use App\Helpers\ToastrHelper;
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
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class TeachingAssignmentController extends Controller
{
    public function __construct(
        protected TeachingAssignmentService $assignmentService
    ) {}

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = TeachingAssignment::with(['kelas', 'mapel', 'user', 'academic_year'])
                ->select('teaching_assignments.*');

            if ($request->filled('academic_year_id')) {
                $query->where('teaching_assignments.academic_year_id', $request->input('academic_year_id'));
            }

            if ($request->filled('kelas_id')) {
                $query->where('teaching_assignments.kelas_id', $request->input('kelas_id'));
            }

            if ($request->filled('user_id')) {
                $query->where('teaching_assignments.user_id', $request->input('user_id'));
            }

            if ($request->filled('mapel_id')) {
                $query->where('teaching_assignments.mapel_id', $request->input('mapel_id'));
            }

            if ($request->filled('status')) {
                $query->where('teaching_assignments.status', $request->input('status'));
            }

            $teacherRoles = ['Administrator', 'Pengurus'];
            if (Role::where('name', 'Guru')->exists()) {
                $teacherRoles[] = 'Guru';
            }
            $teachers = User::role($teacherRoles)->orderBy('name')->get();

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
                ->filterColumn('teacher_name', function ($query, $keyword) {
                    $query->whereHas('user', function ($q) use ($keyword) {
                        $q->where('users.name', 'like', "%{$keyword}%");
                    });
                })
                ->orderColumn('teacher_name', function ($query, $direction) {
                    $query->join('users', 'users.id', '=', 'teaching_assignments.user_id')
                        ->orderBy('users.name', $direction)
                        ->select('teaching_assignments.*');
                })
                ->filterColumn('mapel_name', function ($query, $keyword) {
                    $query->whereHas('mapel', function ($q) use ($keyword) {
                        $q->where('mapels.name', 'like', "%{$keyword}%")
                            ->orWhere('mapels.code', 'like', "%{$keyword}%");
                    });
                })
                ->orderColumn('mapel_name', function ($query, $direction) {
                    $query->join('mapels', 'mapels.id', '=', 'teaching_assignments.mapel_id')
                        ->orderBy('mapels.name', $direction)
                        ->select('teaching_assignments.*');
                })
                ->filterColumn('kelas_name', function ($query, $keyword) {
                    $query->whereHas('kelas', function ($q) use ($keyword) {
                        $q->where('kelas.kelas', 'like', "%{$keyword}%")
                            ->orWhere('kelas.tingkatan', 'like', "%{$keyword}%");
                    });
                })
                ->orderColumn('kelas_name', function ($query, $direction) {
                    $query->join('kelas', 'kelas.id', '=', 'teaching_assignments.kelas_id')
                        ->orderBy('kelas.tingkatan', $direction)
                        ->orderBy('kelas.kelas', $direction)
                        ->select('teaching_assignments.*');
                })
                ->filterColumn('academic_year_name', function ($query, $keyword) {
                    $query->whereHas('academic_year', function ($q) use ($keyword) {
                        $q->where('academic_years.name', 'like', "%{$keyword}%");
                    });
                })
                ->orderColumn('academic_year_name', function ($query, $direction) {
                    $query->join('academic_years', 'academic_years.id', '=', 'teaching_assignments.academic_year_id')
                        ->orderBy('academic_years.name', $direction)
                        ->select('teaching_assignments.*');
                })
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && ! empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->whereHas('user', function ($sq) use ($search) {
                                $sq->where('users.name', 'like', "%{$search}%");
                            })->orWhereHas('mapel', function ($sq) use ($search) {
                                $sq->where('mapels.name', 'like', "%{$search}%")
                                    ->orWhere('mapels.code', 'like', "%{$search}%");
                            })->orWhereHas('kelas', function ($sq) use ($search) {
                                $sq->where('kelas.kelas', 'like', "%{$search}%")
                                    ->orWhere('kelas.tingkatan', 'like', "%{$search}%");
                            })->orWhereHas('academic_year', function ($sq) use ($search) {
                                $sq->where('academic_years.name', 'like', "%{$search}%");
                            })->orWhere('teaching_assignments.status', 'like', "%{$search}%");
                        });
                    }
                })
                ->rawColumns(['mapel_name', 'status', 'action'])
                ->toJson();
        }

        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();
        $activeYear = AcademicYear::where('is_active', true)->first();
        $kelasList = Kelas::orderBy('tingkatan')->orderBy('kelas')->get();
        $mapels = Mapel::active()->with('kelas')->orderBy('name')->get();
        $teacherRoles = ['Administrator', 'Pengurus'];
        if (Role::where('name', 'Guru')->exists()) {
            $teacherRoles[] = 'Guru';
        }
        $teachers = User::role($teacherRoles)->orderBy('name')->get();

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

            ToastrHelper::success('Berhasil menugaskan pengajar mata pelajaran');

            return redirect()->back();
        } catch (DomainException $e) {
            ToastrHelper::warning($e->getMessage());

            return redirect()->back()->withInput();
        } catch (\Throwable $th) {
            Log::error('TeachingAssignmentController store error: '.$th->getMessage(), [
                'user_id' => auth()->id(),
                'request_uri' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
                'exception' => $th,
            ]);
            ToastrHelper::error('Gagal menugaskan pengajar');

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

            ToastrHelper::success('Berhasil memperbarui penugasan mengajar');

            return redirect()->back();
        } catch (DomainException $e) {
            ToastrHelper::warning($e->getMessage());

            return redirect()->back()->withInput();
        } catch (\Throwable $th) {
            Log::error('TeachingAssignmentController update error: '.$th->getMessage(), [
                'user_id' => auth()->id(),
                'request_uri' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
                'exception' => $th,
            ]);
            ToastrHelper::error('Gagal memperbarui penugasan mengajar');

            return redirect()->back()->withInput();
        }
    }

    public function destroy(TeachingAssignment $teachingAssignment)
    {
        try {
            $this->assignmentService->deactivate($teachingAssignment);
            ToastrHelper::success('Berhasil menonaktifkan penugasan mengajar');

            return redirect()->back();
        } catch (\Throwable $th) {
            Log::error('TeachingAssignmentController destroy error: '.$th->getMessage(), [
                'user_id' => auth()->id(),
                'request_uri' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
                'exception' => $th,
            ]);
            ToastrHelper::error('Gagal menonaktifkan penugasan mengajar');

            return redirect()->back();
        }
    }
}
