<?php

namespace App\Http\Controllers\Kelas;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Toastr;
use Yajra\DataTables\Facades\DataTables;

class KelasController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Kelas::query()
                ->select('kelas.*')
                ->orderBy('kelas.tingkatan', 'asc')
                ->orderBy('kelas.kelas', 'asc');

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('action', 'pages.kelas.include.action')
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && ! empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('kelas.tingkatan', 'like', "%{$search}%")
                                ->orWhere('kelas.kelas', 'like', "%{$search}%")
                                ->orWhere('kelas.kode', 'like', "%{$search}%")
                                ->orWhere('kelas.keterangan', 'like', "%{$search}%");
                        });
                    }
                })
                ->toJson();
        }

        return view('pages.kelas.index');
    }

    public function store(Request $request)
    {
        $validate = $request->validate([
            'tingkatan' => 'required|min:3',
            'kelas' => 'required|min:3',
            'keterangan' => 'nullable',
        ]);

        try {
            do {
                $kode = 'KLS-'.Str::upper(Str::random(6));
            } while (Kelas::where('kode', $kode)->exists());

            $validate['kode'] = $kode;
            Kelas::create($validate);
            Toastr::success('Berhasil menambah data');

            return redirect()->back();
        } catch (\Throwable $th) {
            Log::error('KelasController store error: '.$th->getMessage(), [
                'user_id' => auth()->id(),
                'request_uri' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
                'exception' => $th,
            ]);
            Toastr::error('Gagal menambah data');

            return redirect()->back();
        }
    }

    public function update(Request $request, Kelas $kelas)
    {
        $validate = $request->validate([
            'tingkatan' => 'required|min:3',
            'kelas' => 'required|min:3',
            'keterangan' => 'nullable',
        ]);
        try {
            $kelas->update($validate);
            Toastr::success('Berhasil merubah data');

            return redirect()->back();
        } catch (\Throwable $th) {
            Log::error('KelasController update error: '.$th->getMessage(), [
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

    public function destroy(Kelas $kelas)
    {
        if ($kelas->academic_enrollments()->exists()) {
            Toastr::error('Tidak dapat menghapus kelas yang memiliki riwayat akademik pendaftaran santri.');

            return redirect()->back();
        }

        if ($kelas->mapels()->exists()) {
            Toastr::error('Tidak dapat menghapus kelas yang memiliki data mata pelajaran.');

            return redirect()->back();
        }

        if ($kelas->wali_kelas_assignments()->exists()) {
            Toastr::error('Tidak dapat menghapus kelas yang memiliki data penugasan wali kelas.');

            return redirect()->back();
        }

        if ($kelas->teaching_assignments()->exists()) {
            Toastr::error('Tidak dapat menghapus kelas yang memiliki data penugasan mengajar.');

            return redirect()->back();
        }

        if ($kelas->classSchedules()->exists()) {
            Toastr::error('Tidak dapat menghapus kelas yang memiliki data jadwal pelajaran.');

            return redirect()->back();
        }

        try {
            $kelas->delete();
            Toastr::success('Berhasil menghapus data');

            return redirect()->back();
        } catch (\Throwable $th) {
            Log::error('KelasController destroy error: '.$th->getMessage(), [
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
}
