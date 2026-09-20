<?php

namespace App\Providers;

use App\Models\Kamar;
use App\Models\Kelas;
use App\Models\Santri;
use App\Models\Setting;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        view()->composer('pages.santri.*', function ($view) {
            $kelas = Kelas::all();
            $view->with('classes', $kelas);
            $kamar = Kamar::all();
            $view->with('badroom', $kamar);
        });
        view()->composer('pages.users.index', function ($view) {
            $roles = Role::all();
            $view->with('roles', $roles);
        });
        view()->composer('pages.transfer.index', function ($view) {
            $santris = Santri::whereHas('tabungan')->with('user')->get();
            $view->with('santris', $santris);
        });
        view()->composer('pages.users.*', function ($view) {
            $roles = Role::all();
            $view->with('roles', $roles);
        });
        view()->composer('pages.saldo_debit.index', function ($view) {
            $santri = Santri::with('user')->get();
            $view->with('santri', $santri);
        });
        view()->composer('layouts.*', function ($view) {
            $setting = Setting::first();
            $view->with('setting', $setting);
        });
    }
}
