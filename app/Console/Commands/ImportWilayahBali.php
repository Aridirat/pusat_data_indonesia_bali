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
        $namaProv = 'BALI';

        $batchSize = 500;
        $dataInsert = [];

        // =========================
        // INSERT PROVINSI
        // =========================
        DB::table('location')->insert([
            'kode_provinsi' => $kodeProv,
            'kode_kabupaten' => '0',
            'kode_kecamatan' => '0',
            'kode_desa' => '0',
            'provinsi' => $namaProv,
            'kabupaten' => 'ALL',
            'kecamatan' => 'ALL',
            'desa' => 'ALL',
            'banjar' => 'ALL',
            'rt' => 'ALL'
        ]);

        $this->info("Mengambil data kabupaten Bali...");

        $responseKab = Http::get('https://sipedas.pertanian.go.id/api/wilayah/list_kab', [
            'thn' => $tahun,
            'lvl' => 12,
            'pro' => $kodeProv
        ]);

        $kabupaten = $responseKab->json()['output'] ?? [];

        foreach ($kabupaten as $kodeKab => $namaKab) {

            // =========================
            // INSERT KABUPATEN
            // =========================
            DB::table('location')->insert([
                'kode_provinsi' => $kodeProv,
                'kode_kabupaten' => $kodeKab,
                'kode_kecamatan' => '0',
                'kode_desa' => '0',
                'provinsi' => $namaProv,
                'kabupaten' => $namaKab,
                'kecamatan' => 'ALL',
                'desa' => 'ALL',
                'banjar' => 'ALL',
                'rt' => 'ALL'
            ]);

            $responseKec = Http::get('https://sipedas.pertanian.go.id/api/wilayah/list_kec', [
                'thn' => $tahun,
                'lvl' => 13,
                'pro' => $kodeProv,
                'kab' => $kodeKab
            ]);

            $kecamatan = $responseKec->json()['output'] ?? [];

            foreach ($kecamatan as $kodeKec => $namaKec) {

                // =========================
                // INSERT KECAMATAN
                // =========================
                DB::table('location')->insert([
                    'kode_provinsi' => $kodeProv,
                    'kode_kabupaten' => $kodeKab,
                    'kode_kecamatan' => $kodeKec,
                    'kode_desa' => '0',
                    'provinsi' => $namaProv,
                    'kabupaten' => $namaKab,
                    'kecamatan' => $namaKec,
                    'desa' => 'ALL',
                    'banjar' => 'ALL',
                    'rt' => 'ALL'
                ]);

                $responseDes = Http::get('https://sipedas.pertanian.go.id/api/wilayah/list_des', [
                    'thn' => $tahun,
                    'lvl' => 13,
                    'lv2' => 14,
                    'pro' => $kodeProv,
                    'kab' => $kodeKab,
                    'kec' => $kodeKec
                ]);

                $desa = $responseDes->json() ?? [];

                foreach ($desa as $kodeDes => $namaDes) {

                    $dataInsert[] = [
                        'kode_provinsi' => $kodeProv,
                        'kode_kabupaten' => $kodeKab,
                        'kode_kecamatan' => $kodeKec,
                        'kode_desa' => $kodeDes,
                        'provinsi' => $namaProv,
                        'kabupaten' => $namaKab,
                        'kecamatan' => $namaKec,
                        'desa' => $namaDes,
                        'banjar' => 'ALL',
                        'rt' => 'ALL'
                    ];

                    if (count($dataInsert) >= $batchSize) {
                        DB::table('location')->insert($dataInsert);
                        $dataInsert = [];
                    }
                }
            }
        }

        if (!empty($dataInsert)) {
            DB::table('location')->insert($dataInsert);
        }

        $this->info("Import wilayah Bali selesai.");
    }
}