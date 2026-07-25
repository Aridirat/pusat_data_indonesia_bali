<?php

use App\Console\Commands\ExpirePendingLogins;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('session:expire-pending-logins', function () {
    $this->call(ExpirePendingLogins::class);
})->purpose('Expire pending login requests.');

// Sudah benar — namanya cocok dengan signature di ExpirePendingTransaksi.php.
// Cadence hourly aman untuk kasus ini karena UI (blade) dan guard di controller
// (isExpired()) sudah mengecek expired_at on-the-fly, jadi command ini cuma
// "menyapu bersih" status di DB, bukan satu-satunya penjaga.
Schedule::command('transaksi:expire-pending')->hourly();