<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WilayahApiController;
use App\Http\Controllers\Api\DataApiController;

Route::prefix('bali')->group(function () {

    Route::middleware('throttle:30,1')->group(function () {
        Route::get('/kabupaten', [WilayahApiController::class, 'kabupaten']);
    });

    Route::middleware('throttle:120,1')->group(function () {
        Route::get('/kecamatan', [WilayahApiController::class, 'kecamatan']);
        Route::get('/desa',      [WilayahApiController::class, 'desa']);
    });

    Route::prefix('v1')->middleware('throttle:120,1')->group(function () {
        Route::get('/metadata',   [DataApiController::class, 'metadata']);
        Route::get('/data',       [DataApiController::class, 'index']);
        Route::get('/data/{id}',  [DataApiController::class, 'show']);
    });

});