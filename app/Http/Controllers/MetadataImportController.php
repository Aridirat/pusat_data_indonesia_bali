<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Metadata;
use App\Models\ProdusenData;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;


// ─────────────────────────────────────────────────────────────────────
// READ FILTER: Hanya baca kolom yang dibutuhkan (0–24) dan membatasi
// baris per-chunk saat membaca file besar. Ini mencegah PhpSpreadsheet
// ─────────────────────────────────────────────────────────────────────
class ChunkReadFilter implements IReadFilter
{
    private $startRow;
    private $endRow;

    public function __construct($startRow, $endRow)
    {
        $this->startRow = $startRow;
        $this->endRow   = $endRow;
    }

    public function readCell($columnAddress, $row, $worksheetName = '')
    {
        return $row === 1 || ($row >= $this->startRow && $row <= $this->endRow);
    }
}

class MetadataImportController extends Controller
{
    // ─────────────────────────────────────────────────────────────
    // TUNING — sesuaikan jika perlu
    // ─────────────────────────────────────────────────────────────

    /**
     * Jumlah baris Excel yang dibaca per siklus saat streaming.
     * Nilai 300 adalah sweet-spot: cukup besar agar jumlah I/O kecil,
     * cukup kecil agar RAM tidak melonjak pada laptop 8 GB.
     */
    private const READ_CHUNK  = 300;

    /**
     * Jumlah baris yang di-INSERT ke DB dalam satu query.
     * Satu INSERT dengan 100 baris jauh lebih cepat dari 100 INSERT
     * satu-satu, dan lebih aman untuk memori daripada 500 sekaligus.
     */
    private const INSERT_CHUNK = 100;

    // ─────────────────────────────────────────────────────────────
    // MAPPING: indeks kolom Excel (0-based) → nama field
    // ─────────────────────────────────────────────────────────────
    private const COL = [
        0  => 'excel_id',
        1  => 'nama',
        2  => 'alias',
        3  => 'konsep',
        4  => 'definisi',
        5  => 'klasifikasi',
        6  => 'asumsi',
        7  => 'metodologi',
        8  => 'penjelasan_metodologi',
        9  => 'tipe_data',
        10 => 'satuan_data',
        11 => 'tahun_mulai_data',
        12 => 'frekuensi_penerbitan',
        13 => 'tahun_pertama_rilis',
        14 => 'bulan_pertama_rilis',
        15 => 'tanggal_rilis',
        16 => 'produsen_id',
        17 => 'nama_contact_person',
        18 => 'nomor_contact_person',
        19 => 'email_contact_person',
        20 => 'tag',
        21 => 'nama_rujukan',
        22 => 'link_rujukan',
        23 => 'gambar_rujukan',
        24 => 'flag_desimal',
        25 => 'tipe_group',
        26 => 'group_by',
        27 => '_status_excel',
    ];

    private const STRING_DASH = [
        'konsep', 'definisi', 'klasifikasi',
        'metodologi', 'penjelasan_metodologi',
        'tipe_data', 'satuan_data', 'tahun_mulai_data',
        'frekuensi_penerbitan',
        'nama_contact_person', 'nomor_contact_person', 'email_contact_person',
    ];

    // Regex untuk strip suffix wilayah dari ALIAS
    private const ALIAS_WILAYAH = [
        '/\s+di\s+(Kabupaten|Kab\.?|Kecamatan|Kec\.?|Kota|Provinsi|Prov\.?|Desa|Kelurahan|Kel\.?)\s+[\w\s]+$/iu',
        '/\s+(Kabupaten|Kab\.|Kecamatan|Kec\.|Kota|Provinsi|Prov\.|Desa|Kelurahan|Kel\.)\s+\w+(\s+\w+)*$/iu',
        '/\s+(Sukawati|Blahbatuh|Tampaksiring|Tegallalang|Payangan)\s*$/iu',
        '/\s+(Badung|Bangli|Buleleng|Jembrana|Karangasem|Klungkung|Tabanan|Denpasar)\s*$/iu',
        '/\s+(Utara|Selatan|Barat|Timur|Tengah)\s*$/iu',
        '/\s+(Ubud|Gianyar)\s*$/iu',
    ];

    // ═════════════════════════════════════════════════════════════
    // PREVIEW — POST /metadata/import/preview
    // Baca seluruh file untuk menampilkan ringkasan ke user.
    // Preview tidak perlu chunked karena hanya membaca, tidak insert.
    // ═════════════════════════════════════════════════════════════
    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:20480',
        ], [
            'file.required' => 'File Excel wajib diupload.',
            'file.mimes'    => 'Format file harus .xlsx atau .xls.',
            'file.max'      => 'Ukuran file maksimal 20 MB.',
        ]);

        try {
            $spreadsheet = IOFactory::load($request->file('file')->getRealPath());
            $rows        = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
            array_shift($rows); // hapus baris header

            // Bebaskan spreadsheet dari memori segera setelah data diambil
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

            // Hash map nama DB lowercase → O(1) lookup
            $existingInDb = Metadata::pluck('nama')
                ->map(fn($n) => $this->dedupKey($n))
                ->flip()->all();

            // cache produsen agar tidak query berulang
            $produsenCache = ProdusenData::pluck('nama_produsen', 'produsen_id')->toArray();

            $valid   = [];
            $skipped = [];
            $seen    = [];
            $rowNum  = 2;

            foreach ($rows as $raw) {
                if (empty(array_filter($raw, fn($v) => $v !== null && $v !== ''))) {
                    $rowNum++;
                    continue;
                }

                $r   = $this->parseRow($raw);
                $key = $this->dedupKey($r['nama']);

                if (isset($seen[$key])) {
                    $skipped[] = [
                        'row'    => $rowNum,
                        'nama'   => $r['nama'],
                        'reason' => 'Duplikat dalam file Excel',
                    ];
                    $rowNum++;
                    continue;
                }

                $seen[$key] = true;

                $produsenNama = '-';

                if (!empty($r['produsen_id']) && isset($produsenCache[$r['produsen_id']])) {
                    $produsenNama = $produsenCache[$r['produsen_id']];
                }

                $valid[] = [
                    'row'              => $rowNum,
                    'nama'             => $r['nama'],
                    'alias'            => $this->normalizeAlias($r['alias'] ?: $r['nama']),
                    'klasifikasi'      => $r['klasifikasi'],
                    'tipe_data'        => $r['tipe_data'],
                    'satuan_data'      => $r['satuan_data'],
                    'tahun_mulai_data' => $r['tahun_mulai_data'],
                    'frekuensi'        => $r['frekuensi_penerbitan'],
                    'produsen'         => $produsenNama,
                    'tag'              => $r['tag'],
                    'exists_in_db'     => isset($existingInDb[$key]),
                ];

                $rowNum++;
            }

            return response()->json([
                'success'      => true,
                'total_rows'   => count($valid) + count($skipped),
                'valid'        => count($valid),
                'new'          => count(array_filter($valid, fn($r) => !$r['exists_in_db'])),
                'dup_db'       => count(array_filter($valid, fn($r) => $r['exists_in_db'])),
                'skipped'      => count($skipped),
                'rows'         => $valid,
                'skipped_rows' => $skipped,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membaca file: ' . $e->getMessage(),
            ], 422);
        }
    }

    // ═════════════════════════════════════════════════════════════
    // IMPORT — POST /metadata/import/store
    //
    // Strategi hemat RAM untuk laptop 8 GB + SSD hampir penuh:
    //
    //  1. File Excel dibaca per READ_CHUNK baris menggunakan
    //     ChunkReadFilter — PhpSpreadsheet hanya membaca segmen kecil
    //     file ke RAM, bukan seluruh file sekaligus.
    //
    //  2. Setiap chunk yang sudah diproses langsung di-INSERT ke DB
    //     dengan INSERT_CHUNK baris per query, lalu array-nya dikosongkan
    //     agar GC PHP bisa membebaskan memori.
    //
    //  3. Seluruh operasi dibungkus satu DB::transaction() sehingga
    //     jika terjadi error di tengah jalan, tidak ada data setengah-jadi
    //     yang masuk ke database.
    //
    //  4. existingInDb dimuat SEKALI di awal sebagai hash map, bukan
    //     query per-baris (menghindari N+1 query problem).
    //
    //  5. ProdusenData di-cache dalam array lokal agar nama produsen
    //     tidak di-query ulang untuk setiap baris yang sama.
    // ═════════════════════════════════════════════════════════════
    public function store(Request $request)
    {
        $request->validate([
            'file'                => 'required|file|mimes:xlsx,xls|max:20480',
            'skip_existing'       => 'nullable|boolean',
            'produsen_default_id' => 'nullable|exists:produsen_data,produsen_id',
        ]);

        $filePath          = $request->file('file')->getRealPath();
        $skipExisting      = $request->boolean('skip_existing', true);
        $defaultProdusenId = $request->input('produsen_default_id');
        $userId            = Auth::user()->user_id;
        $now               = now()->toDateTimeString();

        try {
            // ── Persiapan data lookup sekali di awal ──────────────
            $existingInDb = Metadata::pluck('nama')
                ->map(fn($n) => $this->dedupKey($n))
                ->flip()->all();

            // Cache produsen berdasarkan nama agar tidak query berulang
            $produsenCache = [];

            // Hitung total baris file untuk keperluan chunking
            $totalRows = $this->countExcelRows($filePath);

            $seen     = [];
            $inserted = 0;
            $skipped  = 0;
            $toInsert = [];

            DB::transaction(function () use (
                $filePath, $skipExisting, $defaultProdusenId,
                $userId, $now, $totalRows,
                &$existingInDb, &$produsenCache,
                &$seen, &$inserted, &$skipped, &$toInsert
            ) {
                // Iterasi per READ_CHUNK baris (mulai dari baris 2, baris 1 = header)
                for ($startRow = 2; $startRow <= $totalRows; $startRow += self::READ_CHUNK) {
                    $endRow = min($startRow + self::READ_CHUNK - 1, $totalRows);

                    // Baca hanya segmen baris ini ke RAM
                    $reader = IOFactory::createReaderForFile($filePath);
                    $reader->setReadFilter(new ChunkReadFilter($startRow, $endRow));
                    $reader->setReadDataOnly(true); // skip style/format → lebih cepat

                    $spreadsheet = $reader->load($filePath);
                    $chunkRows   = $spreadsheet->getActiveSheet()
                                               ->toArray(null, true, true, false);

                    // Bebaskan memori segera setelah data diambil
                    $spreadsheet->disconnectWorksheets();
                    unset($spreadsheet, $reader);

                    // Hapus baris header jika ikut terbaca (terjadi di chunk pertama)
                    if (!empty($chunkRows) && $chunkRows[0][0] !== null && !is_numeric($chunkRows[0][0])) {
                        array_shift($chunkRows);
                    }

                    foreach ($chunkRows as $raw) {
                        if (empty(array_filter($raw, fn($v) => $v !== null && $v !== ''))) continue;

                        $r   = $this->parseRow($raw);
                        $key = $this->dedupKey($r['nama']);

                        if (isset($seen[$key]))                          { $skipped++; continue; }
                        $seen[$key] = true;

                        if ($skipExisting && isset($existingInDb[$key])) { $skipped++; continue; }

                        // Resolusi produsen_id dengan cache lokal
                        $produsenId = is_numeric($r['produsen_id']) ? (int)$r['produsen_id'] : null;
                        if (!$produsenId && !empty($r['_nama_produsen'])) {
                            $namaProd = trim($r['_nama_produsen']);
                            if (!isset($produsenCache[$namaProd])) {
                                $p = ProdusenData::where('nama_produsen', $namaProd)->value('produsen_id');
                                $produsenCache[$namaProd] = $p; // null jika tidak ketemu
                            }
                            $produsenId = $produsenCache[$namaProd];
                        }
                        $produsenId = $produsenId ?? $defaultProdusenId;

                        if (!$produsenId) { $skipped++; continue; }

                        $toInsert[] = $this->buildRow($r, $produsenId, $userId, $now);

                        // Flush ke DB setiap INSERT_CHUNK baris → bebaskan memori
                        if (count($toInsert) >= self::INSERT_CHUNK) {
                            DB::table('metadata')->insert($toInsert);
                            $inserted += count($toInsert);
                            $toInsert  = [];
                        }
                    }

                    unset($chunkRows); // bantu GC
                } // end for (chunk loop)

                // Insert sisa baris yang belum di-flush
                if (!empty($toInsert)) {
                    DB::table('metadata')->insert($toInsert);
                    $inserted += count($toInsert);
                    $toInsert  = [];
                }
            }); // end DB::transaction

            return response()->json([
                'success'  => true,
                'inserted' => $inserted,
                'skipped'  => $skipped,
                'message'  => "$inserted metadata berhasil diimport. $skipped baris dilewati.",
                'redirect' => route('metadata.approval'),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal import: ' . $e->getMessage(),
            ], 422);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // HELPER: countExcelRows
    // Hitung jumlah baris terpakai tanpa memuat seluruh konten sel.
    // Dipakai untuk menentukan range loop chunking.
    // ─────────────────────────────────────────────────────────────
    private function countExcelRows(string $filePath): int
    {
        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);
        $count       = $spreadsheet->getActiveSheet()->getHighestDataRow();
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet, $reader);
        return $count;
    }

    // ─────────────────────────────────────────────────────────────
    // HELPER: parseRow
    // ─────────────────────────────────────────────────────────────
    private function parseRow(array $raw): array
    {
        $r = [];
        foreach (self::COL as $i => $field) {
            $r[$field] = $raw[$i] ?? null;
        }

        foreach (self::STRING_DASH as $field) {
            if (is_null($r[$field]) || trim((string)$r[$field]) === '') {
                $r[$field] = '-';
            }
        }

        // Tag: JSON array → comma-separated string
        if (!empty($r['tag'])) {
            $decoded = json_decode((string)$r['tag'], true);
            if (is_array($decoded)) {
                $r['tag'] = implode(', ', array_map('trim', $decoded));
            }
        }
        if (empty(trim((string)($r['tag'] ?? '')))) $r['tag'] = '-';

        // Normalisasi tipe_data: "Angka Numerik" → "Numerik"
        if (!empty($r['tipe_data'])) {
            $r['tipe_data'] = str_ireplace('Angka Numerik', 'Numerik', $r['tipe_data']);
        }

        return $r;
    }

    // ─────────────────────────────────────────────────────────────
    // HELPER: dedupKey
    // Lowercase + normalkan spasi. Tidak strip nama wilayah karena
    // "Kec. Sukawati" dan "Kec. Blahbatuh" adalah metadata berbeda.
    // ─────────────────────────────────────────────────────────────
    private function dedupKey(?string $nama): string
    {
        if (empty($nama)) return '';
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', $nama)));
    }

    // ─────────────────────────────────────────────────────────────
    // HELPER: normalizeAlias
    //
    // FIX #1: Alias SELALU dikembalikan sebagai string (tidak pernah null).
    // Logika:
    //   - Jika alias memiliki suffix wilayah → strip wilayah → simpan hasil
    //   - Jika alias TIDAK memiliki suffix wilayah → simpan apa adanya
    //
    // Sebelumnya: jika alias sama dengan input setelah strip → return null
    // Sekarang  : selalu return string agar kolom alias di DB selalu terisi
    //
    // Contoh:
    //   "Kepadatan Penduduk Kecamatan Sukawati" → "Kepadatan Penduduk"
    //   "Jumlah Penduduk"                       → "Jumlah Penduduk" (tidak berubah)
    //   null / ""                               → null (tidak ada data alias sama sekali)
    // ─────────────────────────────────────────────────────────────
    private function normalizeAlias(?string $rawAlias): ?string
    {
        if ($rawAlias === null || trim($rawAlias) === '') return null;

        $cleaned = $rawAlias;
        foreach (self::ALIAS_WILAYAH as $pattern) {
            $cleaned = preg_replace($pattern, '', $cleaned);
        }
        $cleaned = trim(preg_replace('/\s+/', ' ', $cleaned));

        // FIX: kembalikan $cleaned apapun hasilnya (tidak null kalau sama dengan input)
        // Jika proses strip menghapus semua karakter (edge case), fallback ke rawAlias
        return $cleaned !== '' ? $cleaned : trim($rawAlias);
    }

    // ─────────────────────────────────────────────────────────────
    // HELPER: buildRow
    // ─────────────────────────────────────────────────────────────
    private function buildRow(array $r, int $produsenId, int $userId, string $now): array
    {
        $groupBy = null;

        if (is_numeric($r['group_by'])) {
            $exists = DB::table('metadata')
                ->where('metadata_id', (int)$r['group_by'])
                ->exists();

            if ($exists) {
                $groupBy = (int)$r['group_by'];
            }
        }

        return [
            'nama'                   => trim($r['nama']),
            // FIX #1: normalizeAlias sekarang selalu menghasilkan string
            'alias'                  => $this->normalizeAlias($r['alias'] ?: $r['nama']),

            'konsep'                 => $r['konsep'],
            'definisi'               => $r['definisi'],
            'klasifikasi'            => $r['klasifikasi'],
            'asumsi'                 => (!empty($r['asumsi']) && $r['asumsi'] !== '-') ? $r['asumsi'] : null,

            'metodologi'             => $r['metodologi'],
            'penjelasan_metodologi'  => $r['penjelasan_metodologi'],

            'tipe_data'              => $r['tipe_data'],
            'satuan_data'            => $r['satuan_data'],
            'tahun_mulai_data'       => $r['tahun_mulai_data'],

            'frekuensi_penerbitan'   => $r['frekuensi_penerbitan'],
            'tahun_pertama_rilis'    => is_numeric($r['tahun_pertama_rilis'])       ? (int)$r['tahun_pertama_rilis']       : null,
            'bulan_pertama_rilis'    => is_numeric($r['bulan_pertama_rilis']) ? (int)$r['bulan_pertama_rilis'] : null,
            'tanggal_rilis'          => is_numeric($r['tanggal_rilis'])       ? (int)$r['tanggal_rilis']       : null,

            'produsen_id'            => $produsenId,
            'nama_contact_person'    => $r['nama_contact_person'],
            'nomor_contact_person'   => '-',
            'email_contact_person'   => $r['email_contact_person'],

            'tag'                    => $r['tag'],
            'nama_rujukan'           => '-',
            'link_rujukan'           => (!empty($r['link_rujukan']) && $r['link_rujukan'] !== '') ? $r['link_rujukan'] : '-',
            'gambar_rujukan'         => '-',

            'flag_desimal'           => 0,
            'tipe_group'             => is_numeric($r['tipe_group']) ? (int)$r['tipe_group'] : 2,
            'group_by'               => $groupBy,

            'status'                 => Metadata::STATUS_PENDING,
            'date_inputed'           => $now,
            'user_id'                => $userId,
        ];
    }
}