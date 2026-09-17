<?php

use App\Http\Controllers\Api\synchronizationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'throttle:sync-api', 'auth:sanctum', 'role:Administrator|Pengurus'])->group(function () {
    Route::controller(synchronizationController::class)->group(function () {
        // sync kelas
        Route::get('/sync/kelas', 'get_kelas')->name('get.kelas');
        Route::post('/sync/kelas', 'store_kelas')->name('store.kelas');

        // sync santri
        Route::get('/sync/santri', 'get_santri')->name('get.santri');
        Route::post('/sync/santri', 'store_santri')->name('store.santri');
    });
});
