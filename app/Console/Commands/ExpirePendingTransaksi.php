<?php

namespace App\Console\Commands;

use App\Models\Transaksi;
use Illuminate\Console\Command;

class ExpirePendingTransaksi extends Command
{
    protected $signature = 'transaksi:expire-pending';
    protected $description = 'Tandai transaksi pending yang sudah lebih dari 24 jam sebagai expired';

    public function handle()
    {
        // 'expired' (timeout otomatis) — beda dari 'cancelled' (dibatalkan user lewat checkout ulang,
        // lihat TransaksiController::checkout()).
        $count = Transaksi::expiredPending()->update(['status' => 'expired']);

        $this->info("{$count} transaksi pending ditandai expired.");
    }
}