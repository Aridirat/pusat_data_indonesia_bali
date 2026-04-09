<?php

namespace App\Imports;

use App\Models\Data;
// use App\Models\Waktu;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
// use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class DataImport
{
    // ── Konfigurasi ──────────────────────────────────────────
    private const HEADER_ROW   = 3;    
    private const DATA_ROW     = 4;    
    private const BATCH_SIZE   = 200;  
    private const COL_META_ID  = 0;    
    private const COL_META_NM  = 1;    
    private const COL_LOC_ID   = 2;    
    private const COL_LOC_NM   = 3;    
    private const COL_PERIOD   = 4;    

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

    
    private array $timeCache   = [];

    
    private array $existingSet = [];

    public function __construct(int $userId = 0, bool $skipDuplicates = true)
    {
        $this->userId         = $userId ?: (Auth::check() ? Auth::user()->user_id : 0);
        $this->skipDuplicates = $skipDuplicates;
    }

    // ══════════════════════════════════════════════════════════
    // ENTRY POINT 
    // ══════════════════════════════════════════════════════════

    public function preview(string $filePath): array
    {
        [$periodCols, $dataRows] = $this->readExcel($filePath);

        $previewRows = [];
        $errors      = [];
        $duplicates  = [];

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

    public function import(string $filePath): array
    {
        [$periodCols, $dataRows] = $this->readExcel($filePath);

        $this->preloadTimeCache($periodCols);

        $this->buildExistingSet($periodCols);

        $buffer    = [];
        $now       = Carbon::now()->format('Y-m-d H:i:s');
        $insertSet = []; 

        DB::beginTransaction();
        try {
            foreach ($dataRows as $rowNum => $row) {
                $result = $this->parseRow($row, $periodCols, $rowNum, dryRun: false);

                foreach ($result['records'] as $rec) {
                    $key = "{$rec['metadata_id']}_{$rec['location_id']}_{$rec['time_id']}";

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

    private function readExcel(string $filePath): array
    {
        $reader      = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);
        $ws          = $spreadsheet->getSheetByName('Data Import')
                       ?? $spreadsheet->getActiveSheet();

        $maxRow = $ws->getHighestDataRow();
        $maxCol = $ws->getHighestDataColumn();

        $headerRow = [];
        foreach ($ws->getRowIterator(self::HEADER_ROW, self::HEADER_ROW) as $row) {
            foreach ($row->getCellIterator('A', $maxCol) as $cell) {
                $val = $cell->getValue();
                if (is_float($val) && floor($val) == $val) $val = (int)$val;
                $headerRow[] = $val;
            }
        }

        $periodCols = array_slice($headerRow, self::COL_PERIOD);

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

            if (empty(array_filter($rowData, fn($v) => $v !== null && $v !== ''))) continue;

            $dataRows[$r] = $rowData;
        }

        return [$periodCols, $dataRows];
    }

    // ══════════════════════════════════════════════════════════
    // ROW PARSER
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

        if (!$locationId) {
            $errors[] = [
                'message' => "Baris $rowNum: location_id kosong atau tidak valid.",
                'row'     => $rowNum,
            ];
        } else {
            // Opsional: verifikasi location_id ada di DB (pakai cache supaya tidak N+1)
            $exists = DB::table('location')
                ->where('location_id', $locationId)
                ->exists();

            if (!$exists) {
                $errors[] = [
                    'message' => "Baris $rowNum: location_id '$locationId' tidak ditemukan di tabel location.",
                    'row'     => $rowNum,
                ];
                $locationId = null;
            }
        }

        if (!$metadataId || !$locationId) {
            return compact('records', 'errors');
        }

        foreach ($periodCols as $pi => $periodLabel) {
            $colIndex = self::COL_PERIOD + $pi;
            $rawValue = $row[$colIndex] ?? null;

            if ($rawValue === null || $rawValue === '') continue;

            if (!is_numeric($rawValue)) {
                $errors[] = [
                    'message'     => "Baris $rowNum, kolom '$periodLabel': nilai '$rawValue' bukan angka.",
                    'metadata_id' => $metadataId,
                    'location_id' => $locationId,
                    'period'      => $periodLabel,
                ];
                continue;
            }

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

    public function parseTimeLabel(string $label): ?array
    {
        $label = trim($label);

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

        if (preg_match('/^(\d{4})_S([12])$/i', $label, $m)) {
            $year     = (int)$m[1];
            $semester = (int)$m[2];
            $quarter  = $semester === 1 ? 1 : 3;
            return [
                'decade'  => (int)(floor($year / 10) * 10),
                'year'    => $year,
                'quarter' => $quarter,
                'month'   => 0,
                'day'     => 0,
            ];
        }

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
    // PRELOAD HELPERS
    // ══════════════════════════════════════════════════════════

    private function preloadTimeCache(array $periodCols): void
    {
        $paramsMap = [];
        foreach ($periodCols as $label) {
            $p = $this->parseTimeLabel((string)$label);
            if ($p) {
                $key          = strtolower(trim((string)$label));
                $paramsMap[$key] = $p;
            }
        }

        if (empty($paramsMap)) return;

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

    private function buildExistingSet(array $periodCols): void
    {
        $timeIds = [];
        foreach ($periodCols as $label) {
            $tid = $this->resolveTimeId((string)$label);
            if ($tid) $timeIds[] = $tid;
        }
        $timeIds = array_unique($timeIds);

        if (empty($timeIds)) return;

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
    // GETTERS
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