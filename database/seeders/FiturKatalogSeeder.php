<?php

namespace Database\Seeders;

use App\Models\FiturKatalog;
use Illuminate\Database\Seeder;

class FiturKatalogSeeder extends Seeder
{
    public function run(): void
    {
        $fiturs = [
            // Akses data & metadata
            'Akses seluruh data publik',
            'Unduh data dalam semua format',
            'Visualisasi grafik interaktif',

            // Template
            'Personalisasi template tampilan data',
            'Template tidak terbatas',
            'Maksimal 5 template',
            'Maksimal 10 template',
            'Maksimal 25 template',
            'Maksimal 50 template',

            // Sesi & akses akun
            '1 sesi login bersamaan',
            '3 sesi login bersamaan',
            '5 sesi login bersamaan',
            'Akses multi-perangkat',
        ];

        foreach ($fiturs as $nama) {
            FiturKatalog::firstOrCreate(['nama_fitur' => $nama]);
        }
    }
}