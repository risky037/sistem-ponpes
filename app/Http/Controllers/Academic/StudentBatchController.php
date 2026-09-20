<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\StudentBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Toastr;
use Yajra\DataTables\Facades\DataTables;

class StudentBatchController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {
            $batches = StudentBatch::withCount('santris')->orderBy('year', 'desc');

            return DataTables::of($batches)
                ->addIndexColumn()
                ->addColumn('santris_count', function ($row) {
                    return '<span class="badge bg-secondary">'.$row->santris_count.' Santri</span>';
                })
                ->addColumn('action', 'pages.academic.student_batch.include.action')
                ->rawColumns(['santris_count', 'action'])
                ->toJson();
        }

        return view('pages.academic.student_batch.index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'year' => 'required|integer|min:2000|max:2100|unique:student_batches,year',
            'description' => 'nullable|string|max:500',
        ], [
            'year.unique' => 'Angkatan dengan tahun tersebut sudah terdaftar.',
        ]);

        try {
            StudentBatch::create($validated);
            Toastr::success('Berhasil menambah data angkatan');

            return redirect()->route('student-batch.index');
        } catch (\Throwable $th) {
            Log::error('StudentBatchController store error: '.$th->getMessage(), [
                'user_id' => auth()->id(),
                'request_uri' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
                'exception' => $th,
            ]);
            Toastr::error('Gagal menambah data angkatan');

            return redirect()->back()->withInput();
        }
    }

    public function edit(StudentBatch $studentBatch)
    {
        if (request()->ajax() || request()->wantsJson()) {
            return response()->json($studentBatch);
        }

        return view('pages.academic.student_batch.edit', compact('studentBatch'));
    }

    public function update(Request $request, StudentBatch $studentBatch)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'year' => 'required|integer|min:2000|max:2100|unique:student_batches,year,'.$studentBatch->id,
            'description' => 'nullable|string|max:500',
        ], [
            'year.unique' => 'Angkatan dengan tahun tersebut sudah terdaftar.',
        ]);

        try {
            $studentBatch->update($validated);
            Toastr::success('Berhasil memperbarui data angkatan');

            return redirect()->route('student-batch.index');
        } catch (\Throwable $th) {
            Log::error('StudentBatchController update error: '.$th->getMessage(), [
                'user_id' => auth()->id(),
                'request_uri' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
                'exception' => $th,
            ]);
            Toastr::error('Gagal memperbarui data angkatan');

            return redirect()->back()->withInput();
        }
    }

    public function destroy(StudentBatch $studentBatch)
    {
        if ($studentBatch->santris()->exists()) {
            Toastr::error('Tidak dapat menghapus angkatan yang masih memiliki data santri.');

            return redirect()->route('student-batch.index');
        }

        try {
            $studentBatch->delete();
            Toastr::success('Berhasil menghapus data angkatan');

            return redirect()->route('student-batch.index');
        } catch (\Throwable $th) {
            Log::error('StudentBatchController destroy error: '.$th->getMessage(), [
                'user_id' => auth()->id(),
                'request_uri' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
                'exception' => $th,
            ]);
            Toastr::error('Gagal menghapus data angkatan');

            return redirect()->back();
        }
    }
}
