<?php

namespace App\Http\Controllers\Kamar;

use App\Exports\KamarExport;
use App\Helpers\ToastrHelper;
use App\Http\Controllers\Controller;
use App\Models\Kamar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class KamarController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {
            $kamar = Kamar::get();

            return DataTables::of($kamar)
                ->addIndexColumn()
                ->addColumn('action', 'pages.kamar.include.action')
                ->toJson();
        }

        return view('pages.kamar.index');
    }

    public function store(Request $request)
    {
        $validate = $request->validate([
            'nama' => 'required|min:3|unique:kamars,nama',
            'blok' => 'required|min:1',
            'jumlah_santri' => 'required|numeric|min:0',
            'maksimal_santri' => 'required|numeric|min:0',
        ]);

        try {
            do {
                $kode = 'KMR-'.Str::upper(Str::random(6));
            } while (Kamar::where('kode', $kode)->exists());

            $validate['kode'] = $kode;
            Kamar::create($validate);
            ToastrHelper::success('Berhasil menambah data');

            return redirect()->back();
        } catch (\Throwable $th) {
            Log::error('KamarController store error: '.$th->getMessage(), [
                'user_id' => auth()->id(),
                'request_uri' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
                'exception' => $th,
            ]);
            ToastrHelper::error('Gagal menambah data');

            return redirect()->back();
        }
    }

    public function update(Request $request, Kamar $kamar)
    {
        $validate = $request->validate([
            'nama' => "required|min:3|unique:kamars,nama,$kamar->id",
            'blok' => 'required|min:1',
            'jumlah_santri' => 'required|numeric|min:0',
            'maksimal_santri' => 'required|numeric|min:0',
        ]);
        try {
            $kamar->update($validate);
            ToastrHelper::success('Berhasil merubah data');

            return redirect()->back();
        } catch (\Throwable $th) {
            Log::error('KamarController update error: '.$th->getMessage(), [
                'user_id' => auth()->id(),
                'request_uri' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
                'exception' => $th,
            ]);
            ToastrHelper::error('Gagal merubah data');

            return redirect()->back()->withInput();
        }
    }

    public function destroy(Kamar $kamar)
    {
        try {
            if ($kamar->jumlah_santri == 0) {
                $kamar->delete();
                ToastrHelper::success('Berhasil menghapus data');
            } else {
                ToastrHelper::info('Kamar telah diisi, tidak dapat dihapus');
            }

            return redirect()->back();
        } catch (\Throwable $th) {
            Log::error('KamarController destroy error: '.$th->getMessage(), [
                'user_id' => auth()->id(),
                'request_uri' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
                'exception' => $th,
            ]);
            ToastrHelper::error('Gagal menghapus data');

            return redirect()->back();
        }
    }

    public function download()
    {
        $kamar = Kamar::get(['kode', 'nama', 'blok', 'jumlah_santri', 'maksimal_santri']);

        return Excel::download(new KamarExport($kamar), 'kamar.xlsx');
    }
}
