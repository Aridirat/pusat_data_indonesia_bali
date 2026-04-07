<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class ImportWilayahBali extends Command
{
    protected $signature = 'import:wilayah-bali';
    protected $description = 'Import wilayah Bali dari API SIPEDAS dengan batch insert';

    public function handle()
    {
        $tahun = date('Y');
        $kodeProv = '51';
        $namaProv = 'Bali';

        // =========================
        // PROVINSI
        // =========================
        $provId = $kodeProv . '00000000';
        $provNama = "Provinsi {$namaProv}";

        DB::table('location')->updateOrInsert(
            ['location_id' => $provId],
            ['nama_wilayah' => $provNama]
        );

        $this->info("✔ {$provNama}");

        // =========================
        // KABUPATEN
        // =========================
        $this->info("Mengambil data kabupaten...");

        $kabupaten = Http::get('https://sipedas.pertanian.go.id/api/wilayah/list_kab', [
            'thn' => $tahun,
            'lvl' => 12,
            'pro' => $kodeProv
        ])->json()['output'] ?? [];

        foreach ($kabupaten as $kodeKab => $namaKab) {

            $kabId = $kodeProv . str_pad($kodeKab, 2, '0', STR_PAD_LEFT) . '000000';
            $kabNama = "Kabupaten " . ucwords(strtolower($namaKab));

            DB::table('location')->updateOrInsert(
                ['location_id' => $kabId],
                ['nama_wilayah' => $kabNama]
            );

            $this->info("✔ {$kabNama}");

            // =========================
            // KECAMATAN
            // =========================
            $kecamatan = Http::get('https://sipedas.pertanian.go.id/api/wilayah/list_kec', [
                'thn' => $tahun,
                'lvl' => 13,
                'pro' => $kodeProv,
                'kab' => $kodeKab
            ])->json()['output'] ?? [];

            foreach ($kecamatan as $kodeKec => $namaKec) {

                $kecId = $kodeProv
                    . str_pad($kodeKab, 2, '0', STR_PAD_LEFT)
                    . str_pad($kodeKec, 3, '0', STR_PAD_LEFT)
                    . '000';

                $kecNama = "Kecamatan " . ucwords(strtolower($namaKec));

                DB::table('location')->updateOrInsert(
                    ['location_id' => $kecId],
                    ['nama_wilayah' => $kecNama]
                );

                $this->info("  ↳ {$kecNama}");

                // =========================
                // DESA
                // =========================
                $desa = Http::get('https://sipedas.pertanian.go.id/api/wilayah/list_des', [
                    'thn' => $tahun,
                    'pro' => $kodeProv,
                    'kab' => $kodeKab,
                    'kec' => $kodeKec
                ])->json() ?? [];

                foreach ($desa as $kodeDes => $namaDes) {

                    $desaId = $kodeProv
                        . str_pad($kodeKab, 2, '0', STR_PAD_LEFT)
                        . str_pad($kodeKec, 3, '0', STR_PAD_LEFT)
                        . str_pad($kodeDes, 3, '0', STR_PAD_LEFT);

                    $desaNama = "Desa " . ucwords(strtolower($namaDes));

                    DB::table('location')->updateOrInsert(
                        ['location_id' => $desaId],
                        ['nama_wilayah' => $desaNama]
                    );

                    if (app()->environment('local')) {
                        $this->info("     - {$desaNama}");
                    }
                }
            }
        }

        $this->info("✅ Import wilayah Bali selesai.");
    }
}