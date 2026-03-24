<?php

namespace App\Imports;

use App\Models\Data;
use App\Models\Waktu;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * DataImport — Membaca file Excel template metadata dan mengubah
 * kolom periode waktu menjadi record di tabel `data`.
 *
 * Struktur Excel yang didukung (baris 3 = header):
 *   A: metadata_id  B: nama_metadata  C: location_id  D: nama_lokasi
 *   E+: kolom periode (tahunan/semester/quarter/bulanan)
 *
 * Format kolom periode yang dikenali:
 *   Tahunan  → integer/float  2022
 *   Semester → string         2022_S1 / 2022_S2
 *   Quarter  → string         2022_Q1 … 2022_Q4
 *   Bulanan  → string         Jan_2022 … Des_2022
 *
 * Satu sel yang tidak kosong → satu record di tabel data:
 *   (metadata_id, location_id, time_id, number_value, status=PENDING)
 */
class DataImport
{
    // ── Konfigurasi ──────────────────────────────────────────
    private const HEADER_ROW   = 3;    // baris ke-3 berisi header
    private const DATA_ROW     = 4;    // data mulai baris ke-4
    private const BATCH_SIZE   = 200;  // record per bulk insert
    private const COL_META_ID  = 0;    // index A (0-based)
    private const COL_META_NM  = 1;    // index B 
    private const COL_LOC_ID   = 2;    // index C
    private const COL_LOC_NM   = 3;    // index D 
    private const COL_PERIOD   = 4;    // kolom E dan seterusnya

    // Nama bulan Indonesia / Inggris → nomor bulan
    private const BULAN_MAP = [
        'jan'=>1,'feb'=>2,'mar'=>3,'apr'=>4,'mei'=>5,'jun'=>6,
        'jul'=>7,'agu'=>8,'aug'=>8,'sep'=>9,'okt'=>10,'oct'=>10,
        'nov'=>11,'des'=>12,'dec'=>12,
    ];

    // ── State ─────────────────────────────────────────────────
    private int   $userId;
    private bool  $skipDuplicates;
    private array $errors      = [];
    private array $duplicates  = [];
    private int   $imported    = 0;
    private int   $skipped     = 0;

    /** Cache time_id agar query tidak berulang untuk periode yang sama */
    private array $timeCache   = [];

    /** Cache duplikat yang sudah ada di DB sebelum import */
    private array $existingSet = [];

    public function __construct(int $userId = 0, bool $skipDuplicates = true)
    {
        $this->userId         = $userId ?: (Auth::check() ? Auth::user()->user_id : 0);
        $this->skipDuplicates = $skipDuplicates;
    }

    // ══════════════════════════════════════════════════════════
    // ENTRY POINT — dipanggil dari DataController
    // ══════════════════════════════════════════════════════════

    /**
     * Preview: baca file, kembalikan array baris + error + duplikat.
     * TIDAK menyimpan ke DB.
     */
    public function preview(string $filePath): array
    {
        [$periodCols, $dataRows] = $this->readExcel($filePath);

        $previewRows = [];
        $errors      = [];
        $duplicates  = [];

        // Build existing set untuk deteksi duplikat saat preview
        $this->buildExistingSet($periodCols);

        foreach ($dataRows as $rowNum => $row) {
            $result = $this->parseRow($row, $periodCols, $rowNum, dryRun: true);

            foreach ($result['records'] as $rec) {
                $key = "{$rec['metadata_id']}_{$rec['location_id']}_{$rec['time_id']}";
                if (isset($this->existingSet[$key])) {
                    $duplicates[] = array_merge($rec, ['row' => $rowNum]);
                } else {
                    $previewRows[] = array_merge($rec, ['row' => $rowNum]);
                }
            }

            foreach ($result['errors'] as $err) {
                $errors[] = array_merge($err, ['row' => $rowNum]);
            }
        }

        return [
            'success'    => true,
            'rows'       => $previewRows,
            'errors'     => $errors,
            'duplicates' => $duplicates,
            'total_rows' => count($dataRows),
            'valid'      => count($previewRows),
            'duplicate'  => count($duplicates),
            'error'      => count($errors),
            'period_type'=> $this->detectPeriodType($periodCols[0] ?? ''),
            'period_cols'=> $periodCols,
        ];
    }

    /**
     * Import: baca file dan simpan ke DB dalam batch.
     * Return summary.
     */
    public function import(string $filePath): array
    {
        [$periodCols, $dataRows] = $this->readExcel($filePath);

        // Pre-build cache semua time_id yang dibutuhkan (1 query per periode)
        $this->preloadTimeCache($periodCols);

        // Pre-build existing set (hindari duplikat dalam DB)
        $this->buildExistingSet($periodCols);

        $buffer    = [];
        $now       = Carbon::now()->format('Y-m-d H:i:s');
        $insertSet = []; // track duplikat dalam batch yang sedang diproses

        DB::beginTransaction();
        try {
            foreach ($dataRows as $rowNum => $row) {
                $result = $this->parseRow($row, $periodCols, $rowNum, dryRun: false);

                foreach ($result['records'] as $rec) {
                    $key = "{$rec['metadata_id']}_{$rec['location_id']}_{$rec['time_id']}";

                    // Cek duplikat: sudah ada di DB atau di batch saat ini
                    if (isset($this->existingSet[$key]) || isset($insertSet[$key])) {
                        if ($this->skipDuplicates) {
                            $this->skipped++;
                            $this->duplicates[] = $rec;
                            continue;
                        }
                    }

                    $insertSet[$key] = true;
                    $buffer[] = [
                        'user_id'      => $this->userId,
                        'metadata_id'  => $rec['metadata_id'],
                        'location_id'  => $rec['location_id'],
                        'time_id'      => $rec['time_id'],
                        'number_value' => $rec['number_value'],
                        'status'       => Data::STATUS_PENDING,
                        'date_inputed' => $now,
                    ];

                    // Flush batch setiap BATCH_SIZE record
                    if (count($buffer) >= self::BATCH_SIZE) {
                        DB::table('data')->insert($buffer);
                        $this->imported += count($buffer);
                        $buffer = [];
                    }
                }

                foreach ($result['errors'] as $err) {
                    $this->errors[] = array_merge($err, ['row' => $rowNum]);
                }
            }

            // Flush sisa buffer
            if (!empty($buffer)) {
                DB::table('data')->insert($buffer);
                $this->imported += count($buffer);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return [
            'success'  => true,
            'imported' => $this->imported,
            'skipped'  => $this->skipped,
            'errors'   => count($this->errors),
            'message'  => $this->buildSummaryMessage(),
        ];
    }

    // ══════════════════════════════════════════════════════════
    // EXCEL READER
    // ══════════════════════════════════════════════════════════

    /**
     * Baca file Excel, return [periodCols[], dataRows[]].
     * dataRows adalah array of array (0-indexed column).
     */
    private function readExcel(string $filePath): array
    {
        $reader      = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);
        $ws          = $spreadsheet->getSheetByName('Data Import')
                       ?? $spreadsheet->getActiveSheet();

        $maxRow = $ws->getHighestDataRow();
        $maxCol = $ws->getHighestDataColumn();

        // ── Baca header (baris 3) ──────────────────────────────
        $headerRow = [];
        foreach ($ws->getRowIterator(self::HEADER_ROW, self::HEADER_ROW) as $row) {
            foreach ($row->getCellIterator('A', $maxCol) as $cell) {
                $val = $cell->getValue();
                // Nilai numerik (tahun) dari Excel bisa bertipe float
                if (is_float($val) && floor($val) == $val) $val = (int)$val;
                $headerRow[] = $val;
            }
        }

        // Kolom periode = semua kolom setelah index 3 (D)
        $periodCols = array_slice($headerRow, self::COL_PERIOD);

        // ── Baca baris data (baris 4 ke bawah) ────────────────
        $dataRows = [];
        for ($r = self::DATA_ROW; $r <= $maxRow; $r++) {
            $rowData = [];
            foreach ($ws->getRowIterator($r, $r) as $row) {
                foreach ($row->getCellIterator('A', $maxCol) as $cell) {
                    $val = $cell->getValue();
                    if (is_float($val) && floor($val) == $val) $val = (int)$val;
                    $rowData[] = $val;
                }
            }

            // Skip baris kosong
            if (empty(array_filter($rowData, fn($v) => $v !== null && $v !== ''))) continue;

            $dataRows[$r] = $rowData;
        }

        return [$periodCols, $dataRows];
    }

    // ══════════════════════════════════════════════════════════
    // ROW PARSER — ubah 1 baris Excel → array of records
    // ══════════════════════════════════════════════════════════

    private function parseRow(array $row, array $periodCols, int $rowNum, bool $dryRun): array
    {
        $records = [];
        $errors  = [];

        $metadataId = isset($row[self::COL_META_ID]) ? (int)$row[self::COL_META_ID] : null;
        $kodeWilayah = isset($row[self::COL_LOC_ID]) ? (string)$row[self::COL_LOC_ID] : null;
        $metaNama   = $row[self::COL_META_NM] ?? '-';
        $locNama    = $row[self::COL_LOC_NM]  ?? '-';

        $locationId = null;

        if ($kodeWilayah) {

            $kodeWilayah = trim($kodeWilayah);

            $prov = substr($kodeWilayah,0,2);
            $kab  = strlen($kodeWilayah) >= 4 ? substr($kodeWilayah,2,2) : null;
            $kec  = strlen($kodeWilayah) >= 7 ? substr($kodeWilayah,4,3) : null;
            $des  = strlen($kodeWilayah) == 10 ? substr($kodeWilayah,7,3) : null;

            $query = DB::table('location')
                ->where('kode_provinsi',$prov);

            if ($kab) {
                $query->where('kode_kabupaten',$kab);
            }

            if ($kec) {
                $query->where('kode_kecamatan',$kec);
            }

            if ($des) {
                $query->where('kode_desa',$des);
            }

            $location = $query->first();

            if ($location) {
                $locationId = $location->location_id;
            }
        }

if (!$locationId) {
    $errors[] = [
        'message' => "Baris $rowNum: kode wilayah $kodeWilayah tidak ditemukan pada tabel location."
    ];
}

        if (!$metadataId || !$locationId) {
            $errors[] = [
                'message'     => "Baris $rowNum: metadata_id atau location_id kosong.",
                'metadata_id' => $metadataId,
                'location_id' => $locationId,
            ];
            return compact('records', 'errors');
        }

        foreach ($periodCols as $pi => $periodLabel) {
            $colIndex = self::COL_PERIOD + $pi;
            $rawValue = $row[$colIndex] ?? null;

            // Lewati sel kosong
            if ($rawValue === null || $rawValue === '') continue;

            // Nilai harus numerik
            if (!is_numeric($rawValue)) {
                $errors[] = [
                    'message'     => "Baris $rowNum, kolom '$periodLabel': nilai '$rawValue' bukan angka.",
                    'metadata_id' => $metadataId,
                    'location_id' => $locationId,
                    'period'      => $periodLabel,
                ];
                continue;
            }

            // Cari time_id
            $timeId = $this->resolveTimeId((string)$periodLabel);

            if (!$timeId) {
                $errors[] = [
                    'message'     => "Baris $rowNum, kolom '$periodLabel': time_id tidak ditemukan di tabel time.",
                    'metadata_id' => $metadataId,
                    'location_id' => $locationId,
                    'period'      => $periodLabel,
                ];
                continue;
            }

            $records[] = [
                'metadata_id'   => $metadataId,
                'nama_metadata' => $metaNama,
                'location_id'   => $locationId,
                'nama_lokasi'   => $locNama,
                'time_id'       => $timeId,
                'period_label'  => $periodLabel,
                'number_value'  => (float)$rawValue,
            ];
        }

        return compact('records', 'errors');
    }

    // ══════════════════════════════════════════════════════════
    // TIME RESOLVER — kolom Excel → time_id dari tabel time
    // ══════════════════════════════════════════════════════════

    /**
     * Tentukan time_id berdasarkan label kolom periode.
     * Hasil di-cache agar tidak query berulang.
     *
     * Format yang dikenali:
     *   2022        → tahunan  (decade=2020, year=2022, q=0, m=0, d=0)
     *   2022_S1     → semester (decade=2020, year=2022, q=1,  m=0, d=0)   ← semester dipetakan ke quarter
     *   2022_Q3     → quarter  (decade=2020, year=2022, q=3,  m=0, d=0)
     *   Jan_2022    → bulanan  (decade=2020, year=2022, q=0,  m=1, d=0)
     */
    private function resolveTimeId(string $label): ?int
    {
        $cacheKey = strtolower(trim($label));
        if (array_key_exists($cacheKey, $this->timeCache)) {
            return $this->timeCache[$cacheKey];
        }

        $params = $this->parseTimeLabel($label);
        if (!$params) {
            $this->timeCache[$cacheKey] = null;
            return null;
        }

        $timeId = DB::table('time')
            ->where('decade',  $params['decade'])
            ->where('year',    $params['year'])
            ->where('quarter', $params['quarter'])
            ->where('month',   $params['month'])
            ->where('day',     $params['day'])
            ->value('time_id');

        $this->timeCache[$cacheKey] = $timeId;
        return $timeId;
    }

    /**
     * Parsing label kolom → array [decade, year, quarter, month, day].
     * Return null jika format tidak dikenali.
     */
    public function parseTimeLabel(string $label): ?array
    {
        $label = trim($label);

        // ── 1. Tahunan: "2022" ────────────────────────────────
        if (is_numeric($label) && strlen($label) === 4) {
            $year   = (int)$label;
            return [
                'decade'  => (int)(floor($year / 10) * 10),
                'year'    => $year,
                'quarter' => 0,
                'month'   => 0,
                'day'     => 0,
            ];
        }

        // ── 2. Semester: "2022_S1" / "2022_S2" ───────────────
        // Semester dipetakan ke quarter (S1=1, S2=3) agar konsisten
        // dengan struktur tabel time yang hanya punya quarter.
        if (preg_match('/^(\d{4})_S([12])$/i', $label, $m)) {
            $year     = (int)$m[1];
            $semester = (int)$m[2];
            // S1 → Q1 (quarter 1), S2 → Q3 (quarter 3)
            $quarter  = $semester === 1 ? 1 : 3;
            return [
                'decade'  => (int)(floor($year / 10) * 10),
                'year'    => $year,
                'quarter' => $quarter,
                'month'   => 0,
                'day'     => 0,
            ];
        }

        // ── 3. Quarter: "2022_Q1" … "2022_Q4" ────────────────
        if (preg_match('/^(\d{4})_Q([1-4])$/i', $label, $m)) {
            $year    = (int)$m[1];
            $quarter = (int)$m[2];
            return [
                'decade'  => (int)(floor($year / 10) * 10),
                'year'    => $year,
                'quarter' => $quarter,
                'month'   => 0,
                'day'     => 0,
            ];
        }

        // ── 4. Bulanan: "Jan_2022" / "Feb_2022" / dst ────────
        if (preg_match('/^([A-Za-z]{3})_(\d{4})$/', $label, $m)) {
            $bulan = strtolower($m[1]);
            $year  = (int)$m[2];
            $month = self::BULAN_MAP[$bulan] ?? null;
            if ($month) {
                return [
                    'decade'  => (int)(floor($year / 10) * 10),
                    'year'    => $year,
                    'quarter' => 0,
                    'month'   => $month,
                    'day'     => 0,
                ];
            }
        }

        return null;
    }

    /**
     * Deteksi jenis periode dari label kolom pertama.
     */
    private function detectPeriodType(string $label): string
    {
        $label = trim((string)$label);
        if (is_numeric($label) && strlen($label) === 4) return 'tahunan';
        if (preg_match('/^\d{4}_S[12]$/i', $label))      return 'semester';
        if (preg_match('/^\d{4}_Q[1-4]$/i', $label))     return 'quarter';
        if (preg_match('/^[A-Za-z]{3}_\d{4}$/', $label)) return 'bulanan';
        return 'unknown';
    }

    // ══════════════════════════════════════════════════════════
    // PRELOAD HELPERS — minimalkan query saat import massal
    // ══════════════════════════════════════════════════════════

    /**
     * Cache semua time_id yang dibutuhkan dalam satu batch query per periode.
     * Lebih efisien daripada query per-baris.
     */
    private function preloadTimeCache(array $periodCols): void
    {
        // Kumpulkan semua kombinasi dimension yang unik
        $paramsMap = [];
        foreach ($periodCols as $label) {
            $p = $this->parseTimeLabel((string)$label);
            if ($p) {
                $key          = strtolower(trim((string)$label));
                $paramsMap[$key] = $p;
            }
        }

        if (empty($paramsMap)) return;

        // Bangun query OR untuk semua periode sekaligus
        $query = DB::table('time');
        $first = true;
        foreach ($paramsMap as $params) {
            $method = $first ? 'where' : 'orWhere';
            $query->$method(function ($q) use ($params) {
                $q->where('decade',  $params['decade'])
                  ->where('year',    $params['year'])
                  ->where('quarter', $params['quarter'])
                  ->where('month',   $params['month'])
                  ->where('day',     $params['day']);
            });
            $first = false;
        }

        $timeRows = $query->get(['time_id', 'decade', 'year', 'quarter', 'month', 'day']);

        // Reverse-map: dari dimensi → time_id
        foreach ($paramsMap as $cacheKey => $params) {
            foreach ($timeRows as $tr) {
                if ($tr->decade  == $params['decade']  &&
                    $tr->year    == $params['year']    &&
                    $tr->quarter == $params['quarter'] &&
                    $tr->month   == $params['month']   &&
                    $tr->day     == $params['day']) {
                    $this->timeCache[$cacheKey] = $tr->time_id;
                    break;
                }
            }
        }
    }

    /**
     * Build set pasangan (metadata_id, location_id, time_id) yang sudah
     * ada di DB — digunakan untuk deteksi duplikat tanpa query per-baris.
     */
    private function buildExistingSet(array $periodCols): void
    {
        // Kumpulkan semua time_id yang relevan
        $timeIds = [];
        foreach ($periodCols as $label) {
            $tid = $this->resolveTimeId((string)$label);
            if ($tid) $timeIds[] = $tid;
        }
        $timeIds = array_unique($timeIds);

        if (empty($timeIds)) return;

        // Ambil semua data yang sudah ada untuk time_id ini
        $existing = DB::table('data')
            ->whereIn('time_id', $timeIds)
            ->select('metadata_id', 'location_id', 'time_id')
            ->get();

        foreach ($existing as $row) {
            $key = "{$row->metadata_id}_{$row->location_id}_{$row->time_id}";
            $this->existingSet[$key] = true;
        }
    }

    // ══════════════════════════════════════════════════════════
    // GETTERS — dipanggil controller setelah import/preview
    // ══════════════════════════════════════════════════════════

    public function getImportedCount(): int  { return $this->imported;   }
    public function getSkippedCount(): int   { return $this->skipped;    }
    public function getErrors(): array       { return $this->errors;     }
    public function getDuplicates(): array   { return $this->duplicates; }

    private function buildSummaryMessage(): string
    {
        $msg = "Berhasil mengimpor {$this->imported} data.";
        if ($this->skipped > 0)          $msg .= " {$this->skipped} duplikat dilewati.";
        if (count($this->errors) > 0)    $msg .= " " . count($this->errors) . " baris gagal.";
        return $msg;
    }
}