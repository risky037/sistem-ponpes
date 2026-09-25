<?php

namespace App\Http\Controllers;

use App\Helpers\ToastrHelper;
use App\Http\Requests\Profil\AccountRequest;
use App\Http\Requests\Profil\BiodataRequest;
use App\Models\AlamatSantri;
use App\Models\Santri;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Laravel\Facades\Image;

class ProfilController extends Controller
{
    public function show(User $user)
    {
        Gate::authorize('view', $user);
        $user->load(['santri.alamat_santri', 'roles']);

        return view('pages.profil.index', compact('user'));
    }

    public function account(AccountRequest $request, User $user)
    {
        Gate::authorize('updateAccount', $user);

        try {
            $data = $request->validated();
            if (empty($data['password'])) {
                unset($data['password']);
            }
            $user->update($data);
            ToastrHelper::success('Berhasil merubah data');

            return redirect()->back();
        } catch (\Throwable $th) {
            Log::error('ProfilController account error: '.$th->getMessage(), [
                'user_id' => auth()->id(),
                'request_uri' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
                'exception' => $th,
            ]);
            ToastrHelper::error('Gagal merubah data');

            return redirect()->back();
        }
    }

    public function biodata(BiodataRequest $request, User $user)
    {
        Gate::authorize('updateBiodata', $user);

        try {
            $validated = $request->validated();
            $foto = $request->file('foto');
            if ($foto && $foto->isValid()) {
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

                $validated['foto'] = $filename;
            } else {
                $validated['foto'] = $user->santri?->foto ?? 'santri.png';
            }

            if ($user->santri) {
                Santri::where('user_id', $user->id)->update([
                    'jenis_kelamin' => $validated['jenis_kelamin'],
                    'nik' => $validated['nik'],
                    'kk' => $validated['kk'],
                    'whatsapp' => $validated['whatsapp'],
                    'tanggal_lahir' => $validated['tanggal_lahir'],
                    'tempat_lahir' => $validated['tempat_lahir'],
                    'foto' => $validated['foto'],
                ]);

                AlamatSantri::updateOrCreate(
                    ['santri_id' => $user->santri->id],
                    ['alamat_lengkap' => $validated['alamat_lengkap']]
                );
            }
            ToastrHelper::success('Berhasil merubah data');

            return redirect()->back();
        } catch (\Throwable $th) {
            Log::error('ProfilController biodata error: '.$th->getMessage(), [
                'user_id' => auth()->id(),
                'request_uri' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
                'exception' => $th,
            ]);
            ToastrHelper::error('Gagal merubah data');

            return redirect()->back();
        }
    }
}
