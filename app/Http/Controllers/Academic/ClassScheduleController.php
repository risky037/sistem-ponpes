<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ClassSchedule;
use App\Models\Kelas;
use App\Models\TeachingAssignment;
use App\Services\Academic\AcademicScheduleService;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Toastr;
use Yajra\DataTables\Facades\DataTables;

class ClassScheduleController extends Controller
{
    public function __construct(
        protected AcademicScheduleService $scheduleService
    ) {}

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = ClassSchedule::with([
                'kelas',
                'teachingAssignment.mapel',
                'teachingAssignment.user',
                'academicYear',
            ]);

            if ($request->filled('academic_year_id')) {
                $query->where('academic_year_id', $request->input('academic_year_id'));
            }

            if ($request->filled('kelas_id')) {
                $query->where('kelas_id', $request->input('kelas_id'));
            }

            if ($request->filled('day_of_week')) {
                $query->where('day_of_week', $request->input('day_of_week'));
            }

            $days = ClassSchedule::DAYS_OF_WEEK;

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('day_badge', function ($row) {
                    return '<span class="badge bg-light text-dark border">'.e($row->day_of_week).'</span>';
                })
                ->addColumn('time_range', function ($row) {
                    $start = substr($row->start_time, 0, 5);
                    $end = substr($row->end_time, 0, 5);

                    return $start.' - '.$end;
                })
                ->addColumn('kelas_name', function ($row) {
                    return e($row->kelas ? $row->kelas->tingkatan.' - '.$row->kelas->kelas : '-');
                })
                ->addColumn('mapel_name', function ($row) {
                    $mapel = $row->teachingAssignment?->mapel;

                    return e($mapel ? $mapel->name.' ('.$mapel->code.')' : '-');
                })
                ->addColumn('teacher_name', function ($row) {
                    return e($row->teachingAssignment?->user?->name ?? '-');
                })
                ->editColumn('room', function ($row) {
                    return e($row->room ?? '-');
                })
                ->addColumn('action', function ($row) use ($days) {
                    return view('pages.academic.class_schedule.include.action', [
                        'model' => $row,
                        'days' => $days,
                    ]);
                })
                ->rawColumns(['day_badge', 'action'])
                ->toJson();
        }

        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();
        $activeYear = AcademicYear::where('is_active', true)->first();
        $kelasList = Kelas::orderBy('tingkatan')->orderBy('kelas')->get();
        $days = ClassSchedule::DAYS_OF_WEEK;

        $teachingAssignments = TeachingAssignment::with(['kelas', 'mapel', 'user', 'academicYear'])
            ->where('status', TeachingAssignment::STATUS_AKTIF)
            ->get();

        return view('pages.academic.class_schedule.index', [
            'academicYears' => $academicYears,
            'activeYear' => $activeYear,
            'kelasList' => $kelasList,
            'days' => $days,
            'teachingAssignments' => $teachingAssignments,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kelas_id' => 'required|exists:kelas,id',
            'teaching_assignment_id' => 'required|exists:teaching_assignments,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'day_of_week' => 'required|in:'.implode(',', ClassSchedule::DAYS_OF_WEEK),
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'room' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:500',
        ], [
            'end_time.after' => 'Waktu selesai harus lebih lambat dari waktu mulai.',
        ]);

        try {
            $kelas = Kelas::findOrFail($validated['kelas_id']);
            $assignment = TeachingAssignment::findOrFail($validated['teaching_assignment_id']);
            $academicYear = AcademicYear::findOrFail($validated['academic_year_id']);

            $this->scheduleService->createSchedule(
                kelas: $kelas,
                teachingAssignment: $assignment,
                academicYear: $academicYear,
                dayOfWeek: $validated['day_of_week'],
                startTime: $validated['start_time'],
                endTime: $validated['end_time'],
                room: $validated['room'] ?? null,
                notes: $validated['notes'] ?? null
            );

            Toastr::success('Berhasil menambahkan jadwal pelajaran');

            return redirect()->back();
        } catch (DomainException $e) {
            Toastr::warning($e->getMessage());

            return redirect()->back()->withInput();
        } catch (\Throwable $th) {
            Log::error('ClassScheduleController store error: '.$th->getMessage(), [
                'user_id' => auth()->id(),
                'request_uri' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
                'exception' => $th,
            ]);
            Toastr::error('Gagal menambahkan jadwal pelajaran');

            return redirect()->back()->withInput();
        }
    }

    public function update(Request $request, ClassSchedule $classSchedule)
    {
        $validated = $request->validate([
            'day_of_week' => 'required|in:'.implode(',', ClassSchedule::DAYS_OF_WEEK),
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'room' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:500',
        ], [
            'end_time.after' => 'Waktu selesai harus lebih lambat dari waktu mulai.',
        ]);

        try {
            $this->scheduleService->updateSchedule($classSchedule, $validated);
            Toastr::success('Berhasil memperbarui jadwal pelajaran');

            return redirect()->back();
        } catch (DomainException $e) {
            Toastr::warning($e->getMessage());

            return redirect()->back()->withInput();
        } catch (\Throwable $th) {
            Log::error('ClassScheduleController update error: '.$th->getMessage(), [
                'user_id' => auth()->id(),
                'request_uri' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
                'exception' => $th,
            ]);
            Toastr::error('Gagal memperbarui jadwal pelajaran');

            return redirect()->back()->withInput();
        }
    }

    public function destroy(ClassSchedule $classSchedule)
    {
        try {
            $this->scheduleService->deleteSchedule($classSchedule);
            Toastr::success('Berhasil menghapus jadwal pelajaran');

            return redirect()->back();
        } catch (DomainException $e) {
            Toastr::warning($e->getMessage());

            return redirect()->back();
        } catch (\Throwable $th) {
            Log::error('ClassScheduleController destroy error: '.$th->getMessage(), [
                'user_id' => auth()->id(),
                'request_uri' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
                'exception' => $th,
            ]);
            Toastr::error('Gagal menghapus jadwal pelajaran');

            return redirect()->back();
        }
    }
}
