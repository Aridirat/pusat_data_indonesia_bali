<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Ganti dari ENUM('pending','success','failed','cancelled') ke VARCHAR(20).
        // Alasan: status baru (expired, refunded, dll) nggak akan butuh ALTER TABLE
        // lagi ke depannya — validasi nilai cukup dijaga di level aplikasi lewat
        // scope/helper yang sudah ada di model Transaksi (isPending, isSuccess, dst).
        //
        // Raw SQL dipakai karena MODIFY COLUMN enum->varchar tidak didukung native
        // oleh Schema Builder Laravel untuk kasus ini secara portable.
        DB::statement("ALTER TABLE transaksi MODIFY status VARCHAR(20) NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        // Rollback ke ENUM lama. PERHATIAN: kalau sudah ada baris dengan status
        // di luar 4 nilai ini (misal 'expired' atau 'refunded'), rollback akan
        // GAGAL (data truncated) kecuali baris tsb diubah dulu ke status yang valid.
        DB::statement("ALTER TABLE transaksi MODIFY status ENUM('pending', 'success', 'failed', 'cancelled') NOT NULL DEFAULT 'pending'");
    }
};