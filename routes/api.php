<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WilayahApiController;
use App\Http\Controllers\Api\DataApiController;

Route::prefix('bali')->group(function () {

    Route::get('/kabupaten',[WilayahApiController::class,'kabupaten']);
    Route::get('/kecamatan',[WilayahApiController::class,'kecamatan']);
    Route::get('/desa',[WilayahApiController::class,'desa']);

    Route::prefix('v1')->group(function () {

    // Daftar semua metadata aktif
    Route::get('/metadata', [DataApiController::class, 'metadata']);

    // List data (bisa difilter via query params)
    // GET /api/v1/data?metadata_id=3&year=2024&per_page=20
    Route::get('/data', [DataApiController::class, 'index']);

    // Detail satu data
    // GET /api/v1/data/42
    Route::get('/data/{id}', [DataApiController::class, 'show']);

});

// ── Route Ter-autentikasi (butuh Bearer Token — Laravel Sanctum) ──
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {

    // Ambil data berdasarkan template tampilan user
    // GET /api/v1/template/3
    Route::get('/template/{tampilan_id}', [DataApiController::class, 'template']);

});
});