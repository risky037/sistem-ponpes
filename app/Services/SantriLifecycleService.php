<?php

namespace App\Services;

use App\Helpers\Helper;
use App\Helpers\Whatsapp;
use App\Models\AlamatSantri;
use App\Models\KamarSantri;
use App\Models\KelasSantri;
use App\Models\Santri;
use App\Models\User;
use App\Models\WaliSantri;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

class SantriLifecycleService
{
    /**
     * Register a new Santri and associated core accounts and relationships.
     */
    public function register(array $data, ?UploadedFile $foto = null): Santri
    {
        return DB::transaction(function () use ($data, $foto) {
            $date = Carbon::parse($data['tahun_masuk']);
            $replace_delimiter = str_replace('/', '-', $date->toHijri()->isoFormat('L'));
            $get_thn = explode('-', $replace_delimiter);
            $tahun_masuk = end($get_thn);
            $params = [
                'tahun_masuk_hijriyah' => $tahun_masuk,
                'tahun_masuk' => $data['tahun_masuk'],
                'gender' => $data['jenis_kelamin'],
            ];

            $data['no_induk'] = Helper::make_noinduk($params);
            $data['tahun_masuk_hijriyah'] = str_replace('/', '-', $date->toHijri()->isoFormat('L'));
            $data['status'] = 'Santri Aktif';

            if (! empty($data['tanggal_boyong'])) {
                $tgl = Carbon::parse($data['tanggal_boyong']);
                $data['tanggal_boyong_hijriyah'] = str_replace('/', '-', $tgl->toHijri()->isoFormat('LL'));
            } else {
                $data['tanggal_boyong_hijriyah'] = '';
            }

            if (! empty($data['whatsapp'])) {
                $data['whatsapp'] = Whatsapp::make($data['whatsapp']);
            }

            if ($foto) {
                $path = storage_path('app/public/uploads/santri/');
                $filename = $foto->hashName();

                if (! file_exists($path)) {
                    mkdir($path, 0755, true);
                }

                Image::read($foto->getRealPath())
                    ->scaleDown(width: 240, height: 295)
                    ->save($path.$filename);

                $publicPath = public_path('uploads/santri/');
                try {
                    if (! file_exists($publicPath)) {
                        mkdir($publicPath, 0755, true);
                    }
                    @copy($path.$filename, $publicPath.$filename);
                } catch (\Throwable $e) {
                    // Non-critical mirror failure
                }

                $data['foto'] = $filename;
            } else {
                $data['foto'] = 'santri.png';
            }

            $user = User::create([
                'name' => $data['nama_lengkap'],
                'email' => 'santri_'.Str::slug($data['nama_lengkap']).config('app.domain'),
                'password' => Hash::make(! empty($data['password']) ? $data['password'] : 'password'),
            ]);
            $user->assignRole('Santri');

            if (! empty($data['kamar'])) {
                $data['kamar_id'] = $data['kamar'];
            }

            $data['nik'] = $data['nik'] ?? '0000000000000000';
            $data['kk'] = $data['kk'] ?? '0000000000000000';

            $data['user_id'] = $user->id;
            $santri = Santri::create($data);

            if (! empty($data['kamar'])) {
                KamarSantri::firstOrCreate([
                    'santri_id' => $santri->id,
                ], [
                    'kamar_id' => $data['kamar'],
                ]);
            }

            if (! empty($data['kelas'])) {
                KelasSantri::create([
                    'santri_id' => $santri->id,
                    'kelas_id' => $data['kelas'],
                ]);
            }

            if (! empty($data['alamat_lengkap'])) {
                AlamatSantri::create([
                    'santri_id' => $santri->id,
                    'alamat_lengkap' => $data['alamat_lengkap'],
                ]);
            }

            WaliSantri::create([
                'santri_id' => $santri->id,
                'nama_ayah' => $data['nama_ayah'] ?? null,
                'nama_ibu' => $data['nama_ibu'] ?? null,
            ]);

            return $santri;
        });
    }

    /**
     * Update an existing Santri and associated core attributes.
     */
    public function update(Santri $santri, array $data, ?UploadedFile $foto = null): Santri
    {
        return DB::transaction(function () use ($santri, $data, $foto) {
            $date = Carbon::parse($data['tahun_masuk']);
            $replace_delimiter = str_replace('/', '-', $date->toHijri()->isoFormat('L'));
            $get_thn = explode('-', $replace_delimiter);
            $tahun_masuk = end($get_thn);
            $params = [
                'tahun_masuk_hijriyah' => $tahun_masuk,
                'tahun_masuk' => $data['tahun_masuk'],
                'gender' => $data['jenis_kelamin'],
            ];

            if ($data['jenis_kelamin'] !== $santri->jenis_kelamin) {
                $data['no_induk'] = Helper::make_noinduk($params);
            }

            $data['tahun_masuk_hijriyah'] = str_replace('/', '-', $date->toHijri()->isoFormat('L'));
            $data['status'] = ! empty($data['tanggal_boyong']) ? 'Santri Alumni' : 'Santri Aktif';

            if (! empty($data['tanggal_boyong'])) {
                $tgl = Carbon::parse($data['tanggal_boyong']);
                $data['tanggal_boyong_hijriyah'] = str_replace('/', '-', $tgl->toHijri()->isoFormat('LL'));
            } else {
                $data['tanggal_boyong_hijriyah'] = '';
            }

            if (! empty($data['whatsapp'])) {
                $data['whatsapp'] = Whatsapp::make($data['whatsapp']);
            }

            if ($foto) {
                $path = storage_path('app/public/uploads/santri/');
                $filename = $foto->hashName();

                if (! file_exists($path)) {
                    mkdir($path, 0755, true);
                }

                Image::read($foto->getRealPath())
                    ->scaleDown(width: 240, height: 295)
                    ->save($path.$filename);

                $publicPath = public_path('uploads/santri/');
                try {
                    if (! file_exists($publicPath)) {
                        mkdir($publicPath, 0755, true);
                    }
                    @copy($path.$filename, $publicPath.$filename);
                } catch (\Throwable $e) {
                    // Non-critical mirror failure
                }

                $data['foto'] = $filename;
            } else {
                $data['foto'] = $santri->foto ?? 'santri.png';
            }

            if (! array_key_exists('nik', $data) || $data['nik'] === null) {
                unset($data['nik']);
            }

            if (! array_key_exists('kk', $data) || $data['kk'] === null) {
                unset($data['kk']);
            }

            $userData = [];
            if (! empty($data['nama_lengkap'])) {
                $userData['name'] = $data['nama_lengkap'];
                $userData['email'] = 'santri_'.Str::slug($data['nama_lengkap']).config('app.domain');
            }

            if (! empty($data['password'])) {
                $userData['password'] = Hash::make($data['password']);
            }

            if ($santri->user_id && ! empty($userData)) {
                User::where('id', $santri->user_id)->update($userData);
            }

            if (isset($data['alamat_lengkap'])) {
                AlamatSantri::updateOrCreate(
                    ['santri_id' => $santri->id],
                    ['alamat_lengkap' => $data['alamat_lengkap']]
                );
            }

            if (isset($data['kamar'])) {
                $santri->kamar_id = $data['kamar'];
                KamarSantri::updateOrCreate(
                    ['santri_id' => $santri->id],
                    ['kamar_id' => $data['kamar']]
                );
            }

            if (isset($data['kelas'])) {
                KelasSantri::updateOrCreate(
                    ['santri_id' => $santri->id],
                    ['kelas_id' => $data['kelas']]
                );
            }

            if (isset($data['nama_ayah']) || isset($data['nama_ibu'])) {
                WaliSantri::updateOrCreate(
                    ['santri_id' => $santri->id],
                    [
                        'nama_ayah' => $data['nama_ayah'] ?? '',
                        'nama_ibu' => $data['nama_ibu'] ?? '',
                    ]
                );
            }

            unset(
                $data['password'],
                $data['password_confirmation'],
                $data['nama_lengkap'],
                $data['kamar'],
                $data['kelas'],
                $data['alamat_lengkap'],
                $data['nama_ayah'],
                $data['nama_ibu']
            );
            $santri->update($data);

            return $santri->fresh();
        });
    }

    /**
     * Delete a Santri and its associated core data.
     */
    public function delete(Santri $santri): void
    {
        DB::transaction(function () use ($santri) {
            if ($santri->foto && $santri->foto !== 'santri.png') {
                $filePath = "public/uploads/santri/{$santri->foto}";
                if (Storage::exists($filePath)) {
                    Storage::delete($filePath);
                }
                $publicFile = public_path("uploads/santri/{$santri->foto}");
                if (file_exists($publicFile)) {
                    @unlink($publicFile);
                }
            }

            $user = $santri->user;

            $santri->tabungan()->delete();
            $santri->transaksi_tabungan()->delete();
            $santri->alamat_santri()->delete();
            $santri->wali_santri()->delete();
            $santri->kamar_santri()->delete();
            $santri->kelas_santri()->delete();
            $santri->academic_enrollments()->delete();
            $santri->delete();

            $user?->delete();
        });
    }
}
