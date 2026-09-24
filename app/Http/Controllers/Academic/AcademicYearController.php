<?php

namespace App\Http\Controllers\Academic;

use App\Helpers\ToastrHelper;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class AcademicYearController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {
            $academicYears = AcademicYear::query()->orderBy('start_date', 'desc');

            return DataTables::of($academicYears)
                ->addIndexColumn()
                ->editColumn('is_active', function ($row) {
                    return $row->is_active
                        ? '<span class="badge bg-success">Aktif</span>'
                        : '<span class="badge bg-secondary">Tidak Aktif</span>';
                })
                ->addColumn('action', 'pages.academic.academic_year.include.action')
                ->rawColumns(['is_active', 'action'])
                ->toJson();
        }

        return view('pages.academic.academic_year.index');
    }

    public function create()
    {
        return view('pages.academic.academic_year.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('academic_years')->where(fn ($query) => $query->where('semester', $request->input('semester'))),
            ],
            'semester' => 'required|string|in:Ganjil,Genap',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'is_active' => 'nullable|boolean',
        ], [
            'name.unique' => 'Tahun ajaran dengan semester tersebut sudah terdaftar.',
        ]);

        try {
            $validated['is_active'] = $request->boolean('is_active');
            if ($validated['is_active']) {
                AcademicYear::where('is_active', true)->update(['is_active' => false]);
            }

            AcademicYear::create($validated);
            ToastrHelper::success('Berhasil menambah tahun ajaran');

            return redirect()->route('academic-year.index');
        } catch (\Throwable $th) {
            Log::error('AcademicYearController store error: '.$th->getMessage(), [
                'user_id' => auth()->id(),
                'request_uri' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
                'exception' => $th,
            ]);
            ToastrHelper::error('Gagal menambah tahun ajaran');

            return redirect()->back()->withInput();
        }
    }

    public function edit(AcademicYear $academicYear)
    {
        if (request()->ajax() || request()->wantsJson()) {
            return response()->json($academicYear);
        }

        return view('pages.academic.academic_year.edit', compact('academicYear'));
    }

    public function update(Request $request, AcademicYear $academicYear)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('academic_years')
                    ->where(fn ($query) => $query->where('semester', $request->input('semester')))
                    ->ignore($academicYear->id),
            ],
            'semester' => 'required|string|in:Ganjil,Genap',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'is_active' => 'nullable|boolean',
        ], [
            'name.unique' => 'Tahun ajaran dengan semester tersebut sudah terdaftar.',
        ]);

        try {
            $validated['is_active'] = $request->boolean('is_active');
            if ($validated['is_active']) {
                AcademicYear::where('id', '!=', $academicYear->id)->update(['is_active' => false]);
            }

            $academicYear->update($validated);
            ToastrHelper::success('Berhasil memperbarui tahun ajaran');

            return redirect()->route('academic-year.index');
        } catch (\Throwable $th) {
            Log::error('AcademicYearController update error: '.$th->getMessage(), [
                'user_id' => auth()->id(),
                'request_uri' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
                'exception' => $th,
            ]);
            ToastrHelper::error('Gagal memperbarui tahun ajaran');

            return redirect()->back()->withInput();
        }
    }

    public function destroy(AcademicYear $academicYear)
    {
        if ($academicYear->academic_enrollments()->exists()) {
            ToastrHelper::error('Tidak dapat menghapus tahun ajaran yang memiliki data riwayat pendaftaran santri.');

            return redirect()->route('academic-year.index');
        }

        if ($academicYear->wali_kelas_assignments()->exists()) {
            ToastrHelper::error('Tidak dapat menghapus tahun ajaran yang memiliki data penugasan wali kelas.');

            return redirect()->route('academic-year.index');
        }

        if ($academicYear->teaching_assignments()->exists()) {
            ToastrHelper::error('Tidak dapat menghapus tahun ajaran yang memiliki data penugasan mengajar.');

            return redirect()->route('academic-year.index');
        }

        if ($academicYear->classSchedules()->exists()) {
            ToastrHelper::error('Tidak dapat menghapus tahun ajaran yang memiliki data jadwal pelajaran.');

            return redirect()->route('academic-year.index');
        }

        if ($academicYear->calendarEvents()->exists()) {
            ToastrHelper::error('Tidak dapat menghapus tahun ajaran yang memiliki data agenda kalender akademik.');

            return redirect()->route('academic-year.index');
        }

        try {
            $academicYear->delete();
            ToastrHelper::success('Berhasil menghapus tahun ajaran');

            return redirect()->route('academic-year.index');
        } catch (\Throwable $th) {
            Log::error('AcademicYearController destroy error: '.$th->getMessage(), [
                'user_id' => auth()->id(),
                'request_uri' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
                'exception' => $th,
            ]);
            ToastrHelper::error('Gagal menghapus tahun ajaran');

            return redirect()->back();
        }
    }
}
