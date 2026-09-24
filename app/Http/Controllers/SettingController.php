<?php

namespace App\Http\Controllers;

use App\Helpers\ToastrHelper;
use App\Http\Requests\SettingRequest;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Laravel\Facades\Image;

class SettingController extends Controller
{
    public function index()
    {
        $setting = Setting::first();

        return view('pages.setting.index', compact('setting'));
    }

    public function store(SettingRequest $request)
    {
        $validate = $request->validated();
        try {
            $logo = request()->file('logo');
            if (isset($logo) == true) {
                $path = storage_path('app/public/uploads/setting/');
                $filename = $logo->hashName();

                if (! file_exists($path)) {
                    mkdir($path, 0755, true);
                }
                Image::read($logo->getRealPath())
                    ->scaleDown(width: 240, height: 295)
                    ->save($path.$filename);
                $validate['logo'] = $filename;
            }
            $favicon = request()->file('favicon');
            if (isset($favicon) == true) {
                $path = storage_path('app/public/uploads/setting/');
                $filename = $favicon->hashName();

                if (! file_exists($path)) {
                    mkdir($path, 0755, true);
                }

                Image::read($favicon->getRealPath())
                    ->scaleDown(width: 240, height: 295)
                    ->save($path.$filename);
                $validate['favicon'] = $filename;
            }
            $kts_master = request()->file('kts_master');
            if (isset($kts_master) == true) {
                $path = storage_path('app/public/uploads/setting/');
                $filename = $kts_master->hashName();

                if (! file_exists($path)) {
                    mkdir($path, 0755, true);
                }

                Image::read($kts_master->getRealPath())
                    ->scaleDown(width: 240, height: 295)
                    ->save($path.$filename);
                $validate['kts_master'] = $filename;
            }
            Setting::updateOrCreate($validate);
            ToastrHelper::success('Berhasil menyimpan data setting!');

            return redirect()->back();
        } catch (\Throwable $th) {
            Log::error('SettingController store error: '.$th->getMessage(), [
                'user_id' => auth()->id(),
                'request_uri' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
                'exception' => $th,
            ]);
            ToastrHelper::error('Gagal menyimpan data setting!');

            return redirect()->back();
        }
    }

    public function update(SettingRequest $request, Setting $setting)
    {
        $validate = $request->validated();
        try {
            $logo = request()->file('logo');
            if (isset($logo) == true) {
                $path = storage_path('app/public/uploads/setting/');
                $filename = $logo->hashName();
                if (! file_exists($path)) {
                    mkdir($path, 0755, true);
                }
                Image::read($logo->getRealPath())
                    ->scaleDown(width: 240, height: 295)
                    ->save($path.$filename);
                // delete old photo from storage
                if ($setting->logo != null && file_exists($path.$setting->logo)) {
                    unlink($path.$setting->logo);
                }
                $validate['logo'] = $filename;
            }
            $favicon = request()->file('favicon');
            if (isset($favicon) == true) {
                $path = storage_path('app/public/uploads/setting/');
                $filename = $favicon->hashName();
                if (! file_exists($path)) {
                    mkdir($path, 0755, true);
                }
                Image::read($favicon->getRealPath())
                    ->scaleDown(width: 240, height: 295)
                    ->save($path.$filename);
                // delete old photo from storage
                if ($setting->favicon != null && file_exists($path.$setting->favicon)) {
                    unlink($path.$setting->favicon);
                }
                $validate['favicon'] = $filename;
            }
            $kts_master = request()->file('kts_master');
            if (isset($kts_master) == true) {
                $path = storage_path('app/public/uploads/setting/');
                $filename = $kts_master->hashName();
                if (! file_exists($path)) {
                    mkdir($path, 0755, true);
                }
                Image::read($kts_master->getRealPath())
                    ->scaleDown(width: 240, height: 295)
                    ->save($path.$filename);
                // delete old photo from storage
                if ($setting->kts_master != null && file_exists($path.$setting->kts_master)) {
                    unlink($path.$setting->kts_master);
                }
                $validate['kts_master'] = $filename;
            }
            $setting->update($validate);
            ToastrHelper::success('Berhasil merubah data setting!');

            return redirect()->back();
        } catch (\Throwable $th) {
            Log::error('SettingController update error: '.$th->getMessage(), [
                'user_id' => auth()->id(),
                'request_uri' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
                'exception' => $th,
            ]);
            ToastrHelper::error('Gagal merubah data setting!');

            return redirect()->back();
        }
    }
}
