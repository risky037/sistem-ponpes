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
    public function index()
    {
        if (request()->ajax()) {
            $kelas = Kelas::all();

            return DataTables::of($kelas)
                ->addIndexColumn()
                ->addColumn('action', 'pages.kelas.include.action')
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
            Toastr::error('Gagal merubah data');

            return redirect()->back()->withInput();
        }
    }

    public function destroy(Kelas $kelas)
    {
        try {
            $kelas->delete();
            Toastr::success('Berhasil menghapus data');

            return redirect()->back();
        } catch (\Throwable $th) {
            Toastr::error('Gagal menghapus data');

            return redirect()->back();
        }
    }
}
