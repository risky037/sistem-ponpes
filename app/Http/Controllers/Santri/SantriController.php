<?php

namespace App\Http\Controllers\Santri;

use App\Exports\SantriExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\SantriRequest;
use App\Imports\SantriImport;
use App\Models\Santri;
use App\Services\SantriLifecycleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Toastr;
use Yajra\DataTables\Facades\DataTables;

class SantriController extends Controller
{
    public function __construct(
        protected SantriLifecycleService $santriLifecycleService
    ) {}

    public function index(Request $request)
    {
        $santri = Santri::with([
            'user:id,name,email',
            'wali_santri',
        ])->select(
            'santris.id',
            'santris.no_induk',
            'santris.jenis_kelamin',
            'santris.tanggal_lahir',
            'santris.user_id',
            'santris.foto',
            'santris.status',
            'santris.tahun_masuk'
        )->orderBy('santris.id', 'desc');

        if ($request->ajax()) {
            return DataTables::of($santri)
                ->addIndexColumn()
                ->addColumn('wali_santri', function ($row) {
                    return e($row->wali_santri?->nama_ayah ?? $row->wali_santri?->nama_wali ?? '-');
                })
                ->addColumn('action', 'pages.santri.include.action')
                ->filterColumn('user.name', function ($query, $keyword) {
                    $query->whereHas('user', function ($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%");
                    });
                })
                ->orderColumn('user.name', function ($query, $direction) {
                    $query->join('users', 'users.id', '=', 'santris.user_id')
                        ->orderBy('users.name', $direction)
                        ->select('santris.*');
                })
                ->filter(function ($query) use ($request) {
                    if ($request->filled('status')) {
                        $query->where('santris.status', $request->input('status'));
                    }
                    if ($request->filled('tahun_masuk')) {
                        $year = $request->input('tahun_masuk');
                        $query->where(function ($q) use ($year) {
                            $q->whereYear('santris.tahun_masuk', $year)
                                ->orWhere('santris.tahun_masuk', 'like', "{$year}%");
                        });
                    }
                    if ($request->has('search') && ! empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('santris.no_induk', 'like', "%{$search}%")
                                ->orWhere('santris.jenis_kelamin', 'like', "%{$search}%")
                                ->orWhere('santris.status', 'like', "%{$search}%")
                                ->orWhereHas('user', function ($q) use ($search) {
                                    $q->where('name', 'like', "%{$search}%")
                                        ->orWhere('email', 'like', "%{$search}%");
                                });
                        });
                    }
                })
                ->toJson();
        }

        $tahunMasukList = Santri::whereNotNull('tahun_masuk')
            ->selectRaw('DISTINCT substr(tahun_masuk, 1, 4) as year')
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->filter();

        $statusList = ['Santri Aktif', 'Santri Alumni'];

        return view('pages.santri.index', compact('tahunMasukList', 'statusList'));
    }

    public function show(Santri $santri)
    {
        $santri->load([
            'user',
            'wali_santri',
            'kamar_santri.kamar',
            'kelas_santri.kelas',
            'alamat_santri',
            'student_batch',
            'academic_enrollments.academic_year',
            'academic_enrollments.kelas',
            'tabungan',
        ]);

        return view('pages.santri.detail', [
            'item' => $santri,
        ]);
    }

    public function store(SantriRequest $request)
    {
        $validate = $request->validated();
        try {
            $validate['nik'] = $request->input('nik');
            $validate['kk'] = $request->input('kk');
            $validate['password'] = $request->input('password');

            $this->santriLifecycleService->register($validate, $request->file('foto'));
            Toastr::success('Berhasil menambah data');

            return redirect()->back();
        } catch (\Throwable $th) {
            Log::error('SantriController store error: '.$th->getMessage(), [
                'user_id' => auth()->id(),
                'request_uri' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
                'exception' => $th,
            ]);
            Toastr::error('Gagal menambah data');

            return redirect()->back()->withInput();
        }
    }

    public function edit(Santri $santri)
    {
        return view('pages.santri.edit', [
            'item' => $santri->load('user', 'wali_santri', 'kamar_santri', 'kelas_santri', 'alamat_santri', 'student_batch'),
        ]);
    }

    public function update(SantriRequest $request, Santri $santri)
    {
        $validate = $request->validated();
        try {
            $validate['nik'] = $request->input('nik');
            $validate['kk'] = $request->input('kk');
            $validate['password'] = $request->input('password');
            $validate['tanggal_boyong'] = $request->input('tanggal_boyong');

            $this->santriLifecycleService->update($santri, $validate, $request->file('foto'));
            Toastr::success('Berhasil merubah data');

            return redirect()->back();
        } catch (\Throwable $th) {
            Log::error('SantriController update error: '.$th->getMessage(), [
                'user_id' => auth()->id(),
                'request_uri' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
                'exception' => $th,
            ]);
            Toastr::error('Gagal merubah data');

            return redirect()->back()->withInput();
        }
    }

    public function destroy(Santri $santri)
    {
        try {
            $this->santriLifecycleService->delete($santri);
            Toastr::success('Berhasil menghapus data');

            return to_route('santri.index');
        } catch (\Throwable $th) {
            Log::error('SantriController destroy error: '.$th->getMessage(), [
                'user_id' => auth()->id(),
                'request_uri' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
                'exception' => $th,
            ]);
            Toastr::error('Gagal menghapus data');

            return redirect()->back();
        }
    }

    public function print_kts(Santri $santri)
    {
        return view('pages.santri.print', compact('santri'));
    }

    public function download()
    {
        $mime = Storage::mimeType('Format import data santri.xlsx');

        return response()->download(public_path('files/').'Format import data santri.xlsx', 'Format import data santri.xlsx', ['Content-Type' => $mime]);
    }

    public function import(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required',
            ]);
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                Excel::import(new SantriImport, $file);
            }
            Toastr::success('Berhasil import data santri');

            return redirect()->back();
        } catch (\Throwable $th) {
            Toastr::error('Gagal import data santri');

            return redirect()->back();
        }
    }

    public function export(Request $request)
    {
        $status = is_array($request->status) ? ($request->status[0] ?? 'Semua Santri') : ($request->status ?? 'Semua Santri');

        if ($status == 'Semua Santri') {
            $santri = Santri::with(['user', 'wali_santri', 'kamar_santri.kamar', 'kelas_santri.kelas', 'alamat_santri'])->get();
        } else {
            $santri = Santri::with(['user', 'wali_santri', 'kamar_santri.kamar', 'kelas_santri.kelas', 'alamat_santri'])->where('status', $status)->get();
        }

        return Excel::download(new SantriExport($santri), 'Export-data-santri.xlsx');
    }
}
