<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WilayahApiController;
use App\Http\Controllers\Api\DataApiController;

Route::prefix('bali')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Wilayah API
    |--------------------------------------------------------------------------
    | Kabupaten jarang berubah & sering diakses pertama kali → cache-friendly.
    | Kecamatan & Desa dipanggil setiap user ganti pilihan → butuh lebih longgar.
    | Semua pakai throttle terpisah agar satu endpoint tidak memblok yang lain.
    */

    // Kabupaten: dipanggil 1x per page load → throttle ketat sudah cukup
    Route::middleware('throttle:30,1')->group(function () {
        Route::get('/kabupaten', [WilayahApiController::class, 'kabupaten']);
    });

    // Kecamatan & Desa: dipanggil berulang saat user drill-down → lebih longgar
    Route::middleware('throttle:120,1')->group(function () {
        Route::get('/kecamatan', [WilayahApiController::class, 'kecamatan']);
        Route::get('/desa',      [WilayahApiController::class, 'desa']);
    });

    /*
    |--------------------------------------------------------------------------
    | Data API v1
    |--------------------------------------------------------------------------
    | Endpoint data bisa diakses lebih sering (dashboard, filter, dsb.)
    */
    Route::prefix('v1')->middleware('throttle:120,1')->group(function () {
        Route::get('/metadata',   [DataApiController::class, 'metadata']);
        Route::get('/data',       [DataApiController::class, 'index']);
        Route::get('/data/{id}',  [DataApiController::class, 'show']);
    });

});