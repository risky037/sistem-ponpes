<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SyncKelasRequest;
use App\Http\Requests\Api\SyncSantriRequest;
use App\Models\ActivityLog;
use App\Models\Kamar;
use App\Models\Kelas;
use App\Models\Santri;
use App\Models\User;
use App\Models\WaliSantri;
use Carbon\Carbon;
use Helper;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

class synchronizationController extends Controller
{
    // function for sync kelas
    public function get_kelas()
    {
        $kelas = Kelas::all();

        return response()->json([
            'status' => true,
            'message' => 'get all data kelas',
            'data' => $kelas,
        ], 200);
    }

    public function store_kelas(SyncKelasRequest $request)
    {
        $validate = $request->validated();
        try {
            if (empty($validate['kode'])) {
                $validate['kode'] = 'KLS-'.Str::upper(Str::random(6));
            }
            $kelas = Kelas::create($validate);

            // Audit logging for mutation
            $userId = auth()->id() ?? $request->user()?->id;
            ActivityLog::create([
                'user_id' => $userId,
                'activity' => "[SYNC] POST /api/v1/sync/kelas action:create resource:Kelas id:{$kelas->id} at ".now()->toIso8601String(),
            ]);

            Log::info('Synchronization API: Kelas created', [
                'user_id' => $userId,
                'endpoint' => $request->path(),
                'action' => 'create',
                'resource' => 'Kelas',
                'resource_id' => $kelas->id,
                'timestamp' => now()->toIso8601String(),
            ]);

            return response()->json([
                'status' => true,
                'message' => 'successfully created data',
                'data' => $kelas,
            ], 201);
        } catch (\Throwable $th) {
            Log::error('Synchronization API error on store_kelas: '.$th->getMessage(), [
                'user_id' => auth()->id(),
                'request_uri' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
                'exception' => $th,
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Internal server error',
                'errors' => [
                    'server' => ['Internal server error'],
                ],
            ], 500);
        }
    }

    // function for sync santri
    public function get_santri()
    {
        $santri = Santri::with(['user', 'kamar_santri', 'kelas_santri'])->get();

        return response()->json([
            'status' => true,
            'message' => 'get all data santri',
            'data' => $santri,
        ], 200);
    }

    public function store_santri(SyncSantriRequest $request)
    {
        $validate = $request->validated();
        try {
            // get tahun hijriyah
            $date = Carbon::parse($request->tahun_masuk);
            $replace_delimiter = str_replace('/', '-', $date->toHijri()->isoFormat('L'));

            // explode ambil tahun untuk no induk
            $get_thn = explode('-', $replace_delimiter);
            $tahun_masuk = end($get_thn);

            // make no by tahun
            $params = [
                'tahun_masuk_hijriyah' => $tahun_masuk,
                'tahun_masuk' => $request->tahun_masuk,
                'gender' => $request->jenis_kelamin,
            ];
            $validate['no_induk'] = Helper::make_noinduk($params);

            // validate replace with column name
            $validate['kamar_id'] = $validate['kamar'];
            $validate['kelas_id'] = $validate['kelas'];
            $validate['tahun_masuk_hijriyah'] = str_replace('/', '-', $date->toHijri()->isoFormat('L'));
            $validate['status'] = isset($request->tanggal_boyong) == true ? 'Santri Alumni' : 'Santri Aktif';
            $validate['whatsapp'] = '62'.$request->whatsapp;
            $tgl = Carbon::parse($request->tanggal_boyong);
            $validate['tanggal_boyong_hijriyah'] = isset($request->tanggal_boyong) ? str_replace('/', '-', $tgl->toHijri()->isoFormat('LL')) : '';
            $validate['tanggal_lahir'] = sprintf('%04d-%02d-%02d', $request->tahun_lahir, $request->bulan_lahir, $request->tanggal_lahir);
            unset($validate['bulan_lahir'], $validate['tahun_lahir']);
            $foto = $request->file('foto');
            if (isset($foto) == false) {
                $user = User::create([
                    'name' => $request->nama_lengkap,
                    'email' => 'santri_'.Str::slug($request->nama_lengkap).'@digitren.net',
                    'password' => bcrypt('password'),
                ]);
                $validate['user_id'] = $user->id;
                $santri = Santri::create($validate);
                if (! $santri) {
                    $user->delete();
                }
                // insert wali santri
                if ($santri) {
                    WaliSantri::create([
                        'santri_id' => $santri->id,
                        'nama_ayah' => $validate['nama_ayah'],
                        'nama_ibu' => $validate['nama_ibu'],
                    ]);
                }
            } else {
                $path = storage_path('app/public/uploads/santri/');
                $filename = $foto->hashName();

                if (! file_exists($path)) {
                    mkdir($path, 0777, true);
                }

                Image::read($foto->getRealPath())
                    ->scaleDown(width: 400, height: 400)
                    ->save($path.$filename);

                // insert user login santri
                $user = User::create([
                    'name' => $request->nama_lengkap,
                    'email' => 'santri_'.Str::slug($request->nama_lengkap).'@digitren.net',
                    'password' => bcrypt('password'),
                ]);

                // insert santri
                $validate['user_id'] = $user->id;
                $validate['foto'] = $filename;
                $santri = Santri::create($validate);

                // insert wali santri
                WaliSantri::create([
                    'santri_id' => $santri->id,
                    'nama_ayah' => $validate['nama_ayah'],
                    'nama_ibu' => $validate['nama_ibu'],
                ]);
            }

            // update kamar
            $kamar = Kamar::where('id', $validate['kamar_id'])->first();
            if ($kamar) {
                $kamar->update([
                    'jumlah_santri' => $kamar->jumlah_santri + 1,
                ]);
            }

            // Audit logging for mutation
            $userId = auth()->id() ?? $request->user()?->id;
            ActivityLog::create([
                'user_id' => $userId,
                'activity' => "[SYNC] POST /api/v1/sync/santri action:create resource:Santri id:{$santri->id} at ".now()->toIso8601String(),
            ]);

            Log::info('Synchronization API: Santri created', [
                'user_id' => $userId,
                'endpoint' => $request->path(),
                'action' => 'create',
                'resource' => 'Santri',
                'resource_id' => $santri->id,
                'timestamp' => now()->toIso8601String(),
            ]);

            return response()->json([
                'status' => true,
                'message' => 'successfully created data',
                'data' => $santri,
            ], 201);
        } catch (\Throwable $th) {
            Log::error('Synchronization API error on store_santri: '.$th->getMessage(), [
                'user_id' => auth()->id(),
                'request_uri' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
                'exception' => $th,
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Internal server error',
                'errors' => [
                    'server' => ['Internal server error'],
                ],
            ], 500);
        }
    }
}
