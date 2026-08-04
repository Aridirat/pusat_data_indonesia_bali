<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class SatuanSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $satuan = [
            // ── Panjang ─────────────────────────────
            ['nama_satuan' => 'Milimeter', 'simbol' => 'mm', 'nilai_konversi' => 0.001],
            ['nama_satuan' => 'Meter', 'simbol' => 'm', 'nilai_konversi' => 1],
            ['nama_satuan' => 'Kilometer', 'simbol' => 'km', 'nilai_konversi' => 1000],
            ['nama_satuan' => 'Ribu Kilometer', 'simbol' => 'ribu km', 'nilai_konversi' => 1000000],

            // ── Berat ─────────────────────────────
            ['nama_satuan' => 'Gram', 'simbol' => 'g', 'nilai_konversi' => 1],
            ['nama_satuan' => 'Ons', 'simbol' => 'ons', 'nilai_konversi' => 100],
            ['nama_satuan' => 'Kilogram', 'simbol' => 'kg', 'nilai_konversi' => 1000],
            ['nama_satuan' => 'Kuintal', 'simbol' => 'kuintal', 'nilai_konversi' => 100000],
            ['nama_satuan' => 'Ton', 'simbol' => 'ton', 'nilai_konversi' => 1000000],

            // ── Luas ─────────────────────────────
            ['nama_satuan' => 'Meter Persegi', 'simbol' => 'm2', 'nilai_konversi' => 1],
            ['nama_satuan' => 'Hektar', 'simbol' => 'ha', 'nilai_konversi' => 10000],
            ['nama_satuan' => 'Kilometer Persegi', 'simbol' => 'km2', 'nilai_konversi' => 1000000],
            ['nama_satuan' => 'Ribu Hektar', 'simbol' => 'ribu ha', 'nilai_konversi' => 10000000],

            // ── Volume ─────────────────────────────
            ['nama_satuan' => 'Liter', 'simbol' => 'l', 'nilai_konversi' => 1],
            ['nama_satuan' => 'Meter Kubik', 'simbol' => 'm3', 'nilai_konversi' => 1000],
            ['nama_satuan' => 'Barel', 'simbol' => null, 'nilai_konversi' => 158.99], // 1 barel = 158,99 liter (BPS)

            // ── Uang ─────────────────────────────
            ['nama_satuan' => 'Rupiah', 'simbol' => 'Rp', 'nilai_konversi' => 1],
            ['nama_satuan' => 'Ribu Rupiah', 'simbol' => 'ribu Rp', 'nilai_konversi' => 1000],
            ['nama_satuan' => 'Juta Rupiah', 'simbol' => 'juta Rp', 'nilai_konversi' => 1000000],
            ['nama_satuan' => 'Miliar Rupiah', 'simbol' => 'miliar Rp', 'nilai_konversi' => 1000000000],

            // ── Persentase ─────────────────────────────
            ['nama_satuan' => 'Persen', 'simbol' => '%', 'nilai_konversi' => 1],

            // ── Energi ─────────────────────────────
            ['nama_satuan' => 'Watt Jam', 'simbol' => 'Wh', 'nilai_konversi' => 1],
            ['nama_satuan' => 'Kilowatt Jam', 'simbol' => 'Kwh', 'nilai_konversi' => 1000],
            ['nama_satuan' => 'Megawatt Jam', 'simbol' => 'MWh', 'nilai_konversi' => 1000000],

            // Listrik
            ['nama_satuan' => 'Volt', 'simbol' => 'V', 'nilai_konversi' => 1],
            ['nama_satuan' => 'Kilovolt', 'simbol' => 'kV', 'nilai_konversi' => 1000],
            ['nama_satuan' => 'Megavolt', 'simbol' => 'MV', 'nilai_konversi' => 1000000],

            // ── Case-variant yang disatukan (sama persis nilainya,
            //    cuma beda penulisan) ──
            ['nama_satuan' => 'Orang', 'simbol' => 'orang', 'nilai_konversi' => 1],
            ['nama_satuan' => 'Poin', 'simbol' => 'poin', 'nilai_konversi' => 1],

            // ── Standalone: satuan hitung/kategorikal & rate majemuk ──
            // simbol = null (tidak perlu duplikasi nama_satuan)
            // Sengaja TIDAK ikut grup konversi manapun: Knot adalah satuan
            // kecepatan (jarak/waktu, dimensi beda dari Panjang), dan
            // Liter Beras adalah konversi densitas khusus beras (bukan
            // konversi Liter↔Kg yang berlaku umum) — kalau digabung ke
            // grup Panjang/Volume, sistem bisa salah "mengonversi" data
            // lain yang tidak relevan (lihat diskusi KW vs MVA).
            ['nama_satuan' => 'Agen', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Badan', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Balai', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Banjar', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Batang', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Biro', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Btg/Pohon', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Buah', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Celsius', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Derajat', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Dermaga', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Desa', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Desa/Kelurahan', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Dokumen', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Dusun/banjar', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Dusun/banjar Dan Lingkungan', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Ekor', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Fraksi', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Gram/Kap/Hari', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Hari', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Indeks', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Institusi', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Jiwa', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Jiwa/km2', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Judul', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Juta M3/th', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Kal/Kap/Hari', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Kali', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Kamar', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Kapasitas', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Kasus', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Kawasan', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Kcal', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Kecelakaan', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Kegiatan', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Kejahatan', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Kelas', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Kelompok', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Keluarga', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Kelurahan', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Kit', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'KK', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Knot', 'simbol' => null, 'nilai_konversi' => 1], // satuan kecepatan (1 knot = 1,8523 km/jam), bukan panjang
            ['nama_satuan' => 'KT', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Ku/Ha', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Kw/Ha', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Lembaga', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Lingkungan', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Lintasan', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Liter Beras', 'simbol' => null, 'nilai_konversi' => 1], // konversi densitas khusus beras (0,80 kg/liter), bukan Liter umum
            ['nama_satuan' => 'Liter/Detik', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Lokasi', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'LSM', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Mdpl', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'MMSCF', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'MW', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'MW/th', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Objek', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'OKP', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Olah Raga', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Organisasi', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Ormas', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Partai', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Pasang', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Pasangan', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Pelaku Usaha', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Pelanggan', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Penyakit', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Perkara', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Perkumpulan', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Persil', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Perusahaan', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Pos', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Pucuk', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Pulau', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Rasio', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Resto', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Ribu Jiwa', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'RT', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Ruang', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Ruang Kelas', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Rumah Tangga', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Rumah Tangga (KK)', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Rupiah Dan Dollar', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'RW', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Sambungan', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Sertifikat', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Sr', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'SS', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Stasiun', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Surat', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Tahun', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Tangkai', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'TEU', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Toko', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Unit', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Usaha', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Wisatawan', 'simbol' => null, 'nilai_konversi' => 1],
            ['nama_satuan' => 'Yayasan', 'simbol' => null, 'nilai_konversi' => 1],
        ];

        foreach ($satuan as &$row) {
            $row['created_at'] = $now;
            $row['updated_at'] = $now;
        }

        DB::table('satuan')->upsert(
            $satuan,
            ['nama_satuan'],
            ['simbol', 'nilai_konversi', 'updated_at']
        );
    }
}