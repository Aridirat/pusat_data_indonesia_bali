<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class RujukanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            [
                'rujukan_id' => 1,
                'nama_rujukan' => 'Kabupaten Gianyar Dalam Angka',
                'link_rujukan' => 'https://gianyarkab.bps.go.id/id/publication/2021/02/26/5c69263f928a91121cef3e2c',
                'gambar_rujukan' => null,
                'produsen_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'rujukan_id' => 2,
                'nama_rujukan' => 'Provinsi Bali Dalam Angka',
                'link_rujukan' => 'https://bali.bps.go.id/id/publication/2025/02/28/c1546258bf024478ec028d7f',
                'gambar_rujukan' => null,
                'produsen_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($data as $row) {
            DB::table('rujukan')->insert([
                'rujukan_id'=>$row['rujukan_id'],
                'nama_rujukan'=>$row['nama_rujukan'],
                'link_rujukan'=>$row['link_rujukan'],
                'gambar_rujukan'=>$row['gambar_rujukan'],
                'produsen_id'=>$row['produsen_id'],
                'created_at'=>now(),
                'updated_at'=>now(),
            ]);
        }
    }
}