<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\AcademicEnrollment;
use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\TeachingSession;
use App\Models\User;
use App\Services\Academic\AttendanceService;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;
use Toastr;
use Yajra\DataTables\Facades\DataTables;

class AttendanceController extends Controller
{
    public function __construct(
        protected AttendanceService $attendanceService
    ) {}

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = TeachingSession::with([
                'teachingAssignment.kelas',
                'teachingAssignment.mapel',
                'teachingAssignment.user',
                'teachingAssignment.academicYear',
                'classSchedule',
                'attendanceRecords',
            ])
                ->select('teaching_sessions.*')
                ->orderBy('teaching_sessions.session_date', 'desc')
                ->orderBy('teaching_sessions.id', 'desc');

            if ($request->filled('academic_year_id')) {
                $query->whereHas('teachingAssignment', function ($q) use ($request) {
                    $q->where('teaching_assignments.academic_year_id', $request->input('academic_year_id'));
                });
            }

            if ($request->filled('kelas_id')) {
                $query->whereHas('teachingAssignment', function ($q) use ($request) {
                    $q->where('teaching_assignments.kelas_id', $request->input('kelas_id'));
                });
            }

            if ($request->filled('user_id')) {
                $query->whereHas('teachingAssignment', function ($q) use ($request) {
                    $q->where('teaching_assignments.user_id', $request->input('user_id'));
                });
            }

            if ($request->filled('mapel_id')) {
                $query->whereHas('teachingAssignment', function ($q) use ($request) {
                    $q->where('teaching_assignments.mapel_id', $request->input('mapel_id'));
                });
            }

            if ($request->filled('status')) {
                $query->where('teaching_sessions.status', $request->input('status'));
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('formatted_date', function ($row) {
                    return $row->session_date ? $row->session_date->format('d/m/Y') : '-';
                })
                ->addColumn('kelas_name', function ($row) {
                    $kelas = $row->teachingAssignment?->kelas;

                    return e($kelas ? $kelas->tingkatan.' - '.$kelas->kelas : '-');
                })
                ->addColumn('mapel_name', function ($row) {
                    return e($row->teachingAssignment?->mapel?->name ?? '-');
                })
                ->addColumn('teacher_name', function ($row) {
                    return e($row->teachingAssignment?->user?->name ?? '-');
                })
                ->addColumn('attendance_summary', function ($row) {
                    $records = $row->attendanceRecords;
                    $total = $records->count();

                    if ($total === 0) {
                        return '<span class="badge bg-secondary">Belum Diisi</span>';
                    }

                    $hadir = $records->where('status', AttendanceRecord::STATUS_HADIR)->count();
                    $izin = $records->where('status', AttendanceRecord::STATUS_IZIN)->count();
                    $sakit = $records->where('status', AttendanceRecord::STATUS_SAKIT)->count();
                    $alpha = $records->where('status', AttendanceRecord::STATUS_ALPHA)->count();

                    $html = '<span class="badge bg-success me-1">H: '.$hadir.'</span>';
                    if ($izin > 0) {
                        $html .= '<span class="badge bg-info text-dark me-1">I: '.$izin.'</span>';
                    }
                    if ($sakit > 0) {
                        $html .= '<span class="badge bg-warning text-dark me-1">S: '.$sakit.'</span>';
                    }
                    if ($alpha > 0) {
                        $html .= '<span class="badge bg-danger me-1">A: '.$alpha.'</span>';
                    }

                    return $html;
                })
                ->addColumn('status_badge', function ($row) {
                    $class = match ($row->status) {
                        TeachingSession::STATUS_COMPLETED => 'bg-success',
                        TeachingSession::STATUS_CANCELLED => 'bg-danger',
                        default => 'bg-primary',
                    };

                    return '<span class="badge '.$class.'">'.e($row->status).'</span>';
                })
                ->addColumn('action', function ($row) {
                    $disabled = $row->status === TeachingSession::STATUS_CANCELLED ? 'disabled' : '';
                    $manageUrl = route('attendance.manage', $row->id);

                    return '<a href="'.$manageUrl.'" class="btn btn-sm btn-primary '.$disabled.'" title="Kelola Presensi"><i class="bx bx-check-square"></i> Presensi</a>';
                })
                ->filterColumn('kelas_name', function ($query, $keyword) {
                    $query->whereHas('teachingAssignment.kelas', function ($q) use ($keyword) {
                        $q->where('kelas.kelas', 'like', "%{$keyword}%")
                            ->orWhere('kelas.tingkatan', 'like', "%{$keyword}%");
                    });
                })
                ->orderColumn('kelas_name', function ($query, $direction) {
                    $query->join('teaching_assignments', 'teaching_assignments.id', '=', 'teaching_sessions.teaching_assignment_id')
                        ->join('kelas', 'kelas.id', '=', 'teaching_assignments.kelas_id')
                        ->orderBy('kelas.tingkatan', $direction)
                        ->orderBy('kelas.kelas', $direction)
                        ->select('teaching_sessions.*');
                })
                ->filterColumn('mapel_name', function ($query, $keyword) {
                    $query->whereHas('teachingAssignment.mapel', function ($q) use ($keyword) {
                        $q->where('mapels.name', 'like', "%{$keyword}%")
                            ->orWhere('mapels.code', 'like', "%{$keyword}%");
                    });
                })
                ->orderColumn('mapel_name', function ($query, $direction) {
                    $query->join('teaching_assignments', 'teaching_assignments.id', '=', 'teaching_sessions.teaching_assignment_id')
                        ->join('mapels', 'mapels.id', '=', 'teaching_assignments.mapel_id')
                        ->orderBy('mapels.name', $direction)
                        ->select('teaching_sessions.*');
                })
                ->filterColumn('teacher_name', function ($query, $keyword) {
                    $query->whereHas('teachingAssignment.user', function ($q) use ($keyword) {
                        $q->where('users.name', 'like', "%{$keyword}%");
                    });
                })
                ->orderColumn('teacher_name', function ($query, $direction) {
                    $query->join('teaching_assignments', 'teaching_assignments.id', '=', 'teaching_sessions.teaching_assignment_id')
                        ->join('users', 'users.id', '=', 'teaching_assignments.user_id')
                        ->orderBy('users.name', $direction)
                        ->select('teaching_sessions.*');
                })
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && ! empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('teaching_sessions.status', 'like', "%{$search}%")
                                ->orWhereHas('teachingAssignment.kelas', function ($sq) use ($search) {
                                    $sq->where('kelas.kelas', 'like', "%{$search}%")
                                        ->orWhere('kelas.tingkatan', 'like', "%{$search}%");
                                })->orWhereHas('teachingAssignment.mapel', function ($sq) use ($search) {
                                    $sq->where('mapels.name', 'like', "%{$search}%")
                                        ->orWhere('mapels.code', 'like', "%{$search}%");
                                })->orWhereHas('teachingAssignment.user', function ($sq) use ($search) {
                                    $sq->where('users.name', 'like', "%{$search}%");
                                });
                        });
                    }
                })
                ->rawColumns(['attendance_summary', 'status_badge', 'action'])
                ->toJson();
        }

        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();
        $activeYear = AcademicYear::where('is_active', true)->first();
        $kelasList = Kelas::orderBy('tingkatan')->orderBy('kelas')->get();
        $teacherRoles = ['Administrator', 'Pengurus'];
        if (Role::where('name', 'Guru')->exists()) {
            $teacherRoles[] = 'Guru';
        }
        $teachers = User::role($teacherRoles)->orderBy('name')->get();
        $mapels = Mapel::active()->orderBy('name')->get();

        return view('pages.academic.attendance.index', compact('academicYears', 'activeYear', 'kelasList', 'teachers', 'mapels'));
    }

    public function manage(TeachingSession $teachingSession)
    {
        $teachingSession->load([
            'teachingAssignment.kelas',
            'teachingAssignment.mapel',
            'teachingAssignment.user',
            'teachingAssignment.academicYear',
            'classSchedule',
            'attendanceRecords',
        ]);

        $assignment = $teachingSession->teachingAssignment;

        $enrollments = AcademicEnrollment::with('santri.user')
            ->where('kelas_id', $assignment->kelas_id)
            ->where('academic_year_id', $assignment->academic_year_id)
            ->where('status', AcademicEnrollment::STATUS_AKTIF)
            ->get();

        $existingAttendance = $teachingSession->attendanceRecords->keyBy('academic_enrollment_id');

        return view('pages.academic.attendance.manage', compact(
            'teachingSession',
            'enrollments',
            'existingAttendance'
        ));
    }

    public function store(Request $request, TeachingSession $teachingSession)
    {
        $validated = $request->validate([
            'attendance' => 'required|array',
            'attendance.*.academic_enrollment_id' => 'required|exists:academic_enrollments,id',
            'attendance.*.status' => 'required|in:'.implode(',', AttendanceRecord::ALLOWED_STATUSES),
            'attendance.*.notes' => 'nullable|string|max:255',
        ]);

        try {
            $this->attendanceService->bulkMarkAttendance(
                session: $teachingSession,
                records: $validated['attendance'],
                marker: auth()->user()
            );

            Toastr::success('Presensi berhasil disimpan.');

            return redirect()->route('attendance.manage', $teachingSession->id);
        } catch (DomainException $e) {
            Toastr::error($e->getMessage());

            return redirect()->back()->withInput();
        } catch (\Throwable $th) {
            Log::error('AttendanceController store error: '.$th->getMessage(), [
                'user_id' => auth()->id(),
                'session_id' => $teachingSession->id,
                'exception' => $th,
            ]);
            Toastr::error('Gagal menyimpan presensi pembelajaran.');

            return redirect()->back()->withInput();
        }
    }

    public function update(Request $request, AttendanceRecord $attendanceRecord)
    {
        $validated = $request->validate([
            'status' => 'required|in:'.implode(',', AttendanceRecord::ALLOWED_STATUSES),
            'notes' => 'nullable|string|max:255',
        ]);

        try {
            $this->attendanceService->updateAttendance(
                record: $attendanceRecord,
                status: $validated['status'],
                marker: auth()->user(),
                notes: $validated['notes'] ?? null
            );

            Toastr::success('Catatan kehadiran berhasil diperbarui.');

            return redirect()->back();
        } catch (DomainException $e) {
            Toastr::error($e->getMessage());

            return redirect()->back();
        } catch (\Throwable $th) {
            Log::error('AttendanceController update error: '.$th->getMessage(), [
                'user_id' => auth()->id(),
                'record_id' => $attendanceRecord->id,
                'exception' => $th,
            ]);
            Toastr::error('Gagal memperbarui catatan kehadiran.');

            return redirect()->back();
        }
    }
}
