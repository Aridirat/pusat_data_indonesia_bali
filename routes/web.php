<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MetadataController;
use App\Http\Controllers\MetadataImportController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\WaktuController;
use App\Http\Controllers\DataController;
use App\Http\Controllers\DataExportController;
use App\Http\Middleware\IsLogin;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\TwoFactorController;

// ── Auth ─────────────────────────────────────────────────────
Route::get('/login',  [AuthController::class, 'loginView'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout',[AuthController::class, 'logout']);

Route::get('/', function () {
    return redirect()->route('data.index');
})->name('home');

// ─────────────────────────────────────────────────────────────
// AUTHENTICATED ROUTES
// ─────────────────────────────────────────────────────────────
Route::middleware([IsLogin::class])->group(function () {

    // ── Dimensi Lokasi ──────────────────────────────────────
    Route::prefix('dimensi_lokasi')->name('dimensi_lokasi.')->group(function () {
        Route::get('/',       [LocationController::class, 'index'])->name('index');
        Route::get('/create', [LocationController::class, 'create'])->name('create');
        Route::post('/',      [LocationController::class, 'store'])->name('store');
    });

    // ── Dimensi Waktu ────────────────────────────────────────
    Route::prefix('dimensi_waktu')->name('dimensi_waktu.')->group(function () {
        Route::get('/',       [WaktuController::class, 'index'])->name('index');
        Route::get('/create', [WaktuController::class, 'create'])->name('create');
        Route::post('/',      [WaktuController::class, 'store'])->name('store');
    });

    // ── Data ─────────────────────────────────────────────────
    Route::prefix('data')->name('data.')->group(function () {
        Route::get('/',       [DataController::class, 'index'])->name('index');
        Route::get('/create', [DataController::class, 'create'])->name('create');
        Route::post('/',      [DataController::class, 'store'])->name('store');

        // AJAX (di atas wildcard)
        Route::get('/kecamatan',       [DataController::class, 'getKecamatan'])->name('kecamatan');
        Route::get('/desa',            [DataController::class, 'getDesa'])->name('desa');
        Route::get('/search-metadata', [DataController::class, 'searchMetadata'])->name('search_metadata');
        Route::get('/search-year',     [DataController::class, 'searchYear'])->name('search_year');

        // Template
        Route::post('/template',              [DataController::class, 'storeTemplate'])->name('template.store');
        Route::delete('/template/{tampilan}', [DataController::class, 'deleteTemplate'])->name('template.delete');

        // Excel
        Route::post('/preview-excel', [DataController::class, 'previewExcel'])->name('preview_excel');
        Route::post('/import-excel',  [DataController::class, 'importExcel'])->name('import_excel');
        Route::get('/template-excel', [DataController::class, 'downloadTemplateExcel'])->name('template_excel');

        // ── Export Data (Excel / PDF / JSON) ───────────
        // Filter params: metadata_id, kabupaten, year (sama dengan index)
        Route::get('/export/excel', [DataExportController::class, 'excel'])->name('export.excel');
        Route::get('/export/pdf',   [DataExportController::class, 'pdf'])->name('export.pdf');
        Route::get('/export/json',  [DataExportController::class, 'json'])->name('export.json');

        // Approval
        Route::get('/approval',         [DataController::class, 'approval'])->name('approval');
        Route::post('/bulk-approve',    [DataController::class, 'bulkApprove'])->name('bulk_approve');
        Route::post('/{datum}/approve', [DataController::class, 'approve'])->name('approve');
        Route::post('/{datum}/reject',  [DataController::class, 'reject'])->name('reject');

        // Wildcard paling bawah
        Route::get('/{datum}', [DataController::class, 'show'])->name('show');
    });

    // ── Metadata ─────────────────────────────────────────────

    Route::get( '/metadata',            [MetadataController::class, 'index'])->name('metadata.index');
    Route::get( '/metadata/create',     [MetadataController::class, 'create'])->name('metadata.create');
    Route::post('/metadata',            [MetadataController::class, 'store'])->name('metadata.store');
    Route::get( '/metadata/check-nama', [MetadataController::class, 'checkNama'])->name('metadata.check_nama');
    Route::get( '/metadata/approval',   [MetadataController::class, 'approval'])->name('metadata.approval');

    // count_only: digunakan modal untuk preview jumlah data (JSON)
    // export: download file .xlsx
    Route::get('/metadata/export/count', [MetadataController::class, 'exportCount'])->name('metadata.export.count');
    Route::get('/metadata/export',       [MetadataController::class, 'export'])->name('metadata.export');

    // Menghasilkan file .xlsx kosong berisi metadata_id + nama + kolom periode waktu
    Route::get('/metadata/template',     [MetadataController::class, 'exportTemplate'])->name('metadata.template');

    // ── Import Excel ──────────────────────────────────────────
    Route::post('/metadata/import/preview', [MetadataImportController::class, 'preview'])->name('metadata.import.preview');
    Route::post('/metadata/import/store',   [MetadataImportController::class, 'store'])->name('metadata.import.store');

    // ── Bulk Approve (statis, di atas wildcard) ───────────────
    Route::post('/metadata/bulk-approve',     [MetadataController::class, 'bulkApprove'])->name('metadata.bulk_approve');
    Route::post('/metadata/bulk-approve-all', [MetadataController::class, 'bulkApproveAll'])->name('metadata.bulk_approve_all');

    // ── Wildcard /{metadata} — paling bawah ──────────────────
    Route::post('/metadata/{metadata}/approve',    [MetadataController::class, 'approve'])->name('metadata.approve');
    Route::post('/metadata/{metadata}/reject',     [MetadataController::class, 'reject'])->name('metadata.reject');
    Route::post('/metadata/{metadata}/reactivate', [MetadataController::class, 'reactivate'])->name('metadata.reactivate');
    Route::get( '/metadata/{metadata}',            [MetadataController::class, 'detail'])->name('metadata.detail');

    // Password update
    Route::get('/user/password/edit', [PasswordController::class, 'edit'])->name('user-password.edit');
    Route::patch('/user/password', [PasswordController::class, 'update'])->name('user-password.update');

    // Profile update
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Two Factor Authentication
    Route::get('/two-factor', [TwoFactorController::class, 'show'])->name('two-factor.show');
});