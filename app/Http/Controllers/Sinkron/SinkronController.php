<?php

namespace App\Http\Controllers\Sinkron;

use App\Http\Controllers\Controller;
use App\Models\Santri;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Ping;
use Revolution\Google\Sheets\Facades\Sheets;

class SinkronController extends Controller
{
    public function index()
    {
        $data = config('modules.modules');
        if (is_array($data)) {
            foreach ($data as $key => &$module) {
                $syncedAt = Cache::get("modules.sync.{$key}");
                if ($syncedAt !== null) {
                    $module[1] = $syncedAt;
                }
            }
            unset($module);
        }

        return view('pages.sinkronisasi.index', compact('data'));
    }

    public function sync()
    {
        try {
            $condition = Ping::to();
            if ($condition == true) {
                $sheet_id = env('SPREADSHEET_ID', env('SPREDSHEET_ID'));
                if ($sheet_id) {
                    $aktif = Sheets::spreadsheet($sheet_id)->sheet('Santri Aktif')->get()->toArray();
                    if (count($aktif) > 0) {
                        if (count($aktif) == 1) {
                            $santri_aktif = Santri::select('no_induk', 'name', 'provinsi', 'kabupaten', 'kecamatan', 'desa', 'dusun', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir', 'bulan_lahir', 'tahun_lahir', 'nik', 'kk', 'kamars.nama', 'kamars.blok', 'kelas.tingkatan', 'kelas.kelas', 'tahun_masuk', 'tahun_masuk_hijriyah')
                                ->join('users', 'santris.user_id', '=', 'users.id')
                                ->join('kelas', 'santris.kelas_id', '=', 'kelas.id')
                                ->join('kamars', 'santris.kamar_id', '=', 'kamars.id')
                                ->where('status', 'Santri Aktif')
                                ->get()->toArray();

                            $santri_aktif_valid = [];
                            foreach ($santri_aktif as $val) {
                                $santri_aktif_valid[] = array_values($val);
                            }

                            Sheets::spreadsheet($sheet_id)->sheet('Santri Aktif')->append($santri_aktif_valid);
                        } else {
                            $santri_aktif = Santri::select('no_induk', 'name', 'provinsi', 'kabupaten', 'kecamatan', 'desa', 'dusun', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir', 'bulan_lahir', 'tahun_lahir', 'nik', 'kk', 'kamars.nama', 'kamars.blok', 'kelas.tingkatan', 'kelas.kelas', 'tahun_masuk', 'tahun_masuk_hijriyah')
                                ->join('users', 'santris.user_id', '=', 'users.id')
                                ->join('kelas', 'santris.kelas_id', '=', 'kelas.id')
                                ->join('kamars', 'santris.kamar_id', '=', 'kamars.id')
                                ->where('status', 'Santri Aktif')
                                ->get()->toArray();

                            $santri_aktif_sheet = Sheets::spreadsheet($sheet_id)->sheet('Santri Aktif')->get()->toArray();
                            $santri_aktif_valid = [];
                            foreach ($santri_aktif as $val) {
                                $santri_aktif_valid[] = array_values($val);
                            }

                            // Mengonversi variable kedua menjadi daftar ID yang akan dihapus
                            $idsToDelete = array_map(function ($row) {
                                return $row[0];
                            }, array_slice($santri_aktif_sheet, 1));

                            // Menghapus baris dari variable pertama yang ada di variable kedua
                            foreach ($santri_aktif_valid as $key => $row) {
                                if (in_array($row[0], $idsToDelete)) {
                                    unset($santri_aktif_valid[$key]);
                                }
                            }

                            // Reset kembali indeks array
                            $santri_aktif = array_values($santri_aktif_valid);

                            Sheets::spreadsheet($sheet_id)->sheet('Santri Aktif')->append($santri_aktif);
                        }
                    }

                    return response()->json(['success' => true], 200);
                } else {
                    return response()->json(['success' => false, 'message' => 'Silahkan isi ID Spreadsheet terlebih dahulu'], 200);
                }
            }

            return response()->json(['success' => false, 'message' => 'Tidak ada koneksi internet'], 200);
        } catch (\Throwable $th) {
            Log::error('SinkronController sync error: '.$th->getMessage(), [
                'user_id' => auth()->id(),
                'request_uri' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
                'exception' => $th,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
            ], 500);
        }
    }

    public function update(Request $request)
    {
        try {
            $data = $request->input('data');
            $timestamp = is_array($data) ? ($data[1] ?? '') : $data;
            Cache::forever('modules.sync.santri', $timestamp);

            return response()->json(['success' => true, 'message' => 'Berhasil mengubah data']);
        } catch (\Throwable $th) {
            Log::error('SinkronController update error: '.$th->getMessage(), [
                'user_id' => auth()->id(),
                'request_uri' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
                'exception' => $th,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
            ], 500);
        }
    }
}
