<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Protection;
use App\Models\Metadata;
use App\Models\ProdusenData;

class MetadataController extends Controller
{
    const STATUS_PENDING  = 1;
    const STATUS_ACTIVE   = 2;
    const STATUS_INACTIVE = 3;


    public function index(Request $request)
    {
        $query = Metadata::with(['produsen'])
                          ->where('status', self::STATUS_ACTIVE);

        // Search global
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) =>
                $q->where('nama',  'like', "%$s%")
                  ->orWhere('alias', 'like', "%$s%")
                  ->orWhere('tag',   'like', "%$s%")
            );
        }

        // Filter kolom header tabel
        if ($request->filled('filter_nama'))        { $query->where('nama', 'like', '%'.$request->filter_nama.'%'); }
        if ($request->filled('filter_klasifikasi')) { $query->where('klasifikasi', $request->filter_klasifikasi); }
        if ($request->filled('filter_tipe_data'))   { $query->where('tipe_data', $request->filter_tipe_data); }
        if ($request->filled('filter_satuan'))      { $query->where('satuan_data', 'like', '%'.$request->filter_satuan.'%'); }
        if ($request->filled('filter_frekuensi'))   { $query->where('frekuensi_penerbitan', $request->filter_frekuensi); }
        if ($request->filled('filter_produsen_id')) { $query->where('produsen_id', $request->filter_produsen_id); }

        $data = $query->orderBy('metadata_id', 'desc')->paginate(15)->withQueryString();

        // Data dropdown filter
        $klasifikasiList = Metadata::where('status', self::STATUS_ACTIVE)
            ->distinct()->orderBy('klasifikasi')->pluck('klasifikasi')->filter()->values();

        $tipeDataList = Metadata::where('status', self::STATUS_ACTIVE)
            ->distinct()->orderBy('tipe_data')->pluck('tipe_data')->filter()->values();

        $frekuensiList = Metadata::where('status', self::STATUS_ACTIVE)
            ->distinct()->orderBy('frekuensi_penerbitan')->pluck('frekuensi_penerbitan')->filter()->values();

        $produsenList = ProdusenData::whereIn(
                'produsen_id',
                Metadata::where('status', self::STATUS_ACTIVE)->distinct()->pluck('produsen_id')
            )->orderBy('nama_produsen')->get(['produsen_id', 'nama_produsen']);

        $produsenAll = ProdusenData::orderBy('nama_produsen')->get(['produsen_id', 'nama_produsen']);

        $pendingCount = Metadata::where('status', self::STATUS_PENDING)->count();

        return view('pages.metadata.index', compact(
            'data',
            'klasifikasiList', 'tipeDataList', 'frekuensiList',
            'produsenList', 'produsenAll',
            'pendingCount'
        ));
    }

    public function exportCount(Request $request)
    {
        $query = Metadata::where('status', self::STATUS_ACTIVE);
        if ($request->filled('produsen_id')) { $query->where('produsen_id', $request->produsen_id); }
        if ($request->filled('frekuensi'))   { $query->where('frekuensi_penerbitan', $request->frekuensi); }
        return response()->json(['count' => $query->count()]);
    }

    public function export(Request $request)
    {
        $request->validate([
            'produsen_id' => 'nullable|exists:produsen_data,produsen_id',
            'frekuensi'   => 'nullable|string|max:50',
        ]);

        $query = Metadata::where('status', self::STATUS_ACTIVE)
            ->orderBy('metadata_id');

        if ($request->filled('produsen_id')) {
            $query->where('produsen_id', $request->produsen_id);
        }

        if ($request->filled('frekuensi')) {
            $query->where('frekuensi_penerbitan', $request->frekuensi);
        }

        $rows = $query->get();

        $parts = ['Metadata'];
        if ($request->filled('produsen_id')) {
            $p = ProdusenData::find($request->produsen_id);
            if ($p) {
                $parts[] = str_replace(' ', '_', $p->nama_produsen);
            }
        }

        if ($request->filled('frekuensi')) {
            $parts[] = str_replace(' ', '_', $request->frekuensi);
        }

        $parts[] = now()->format('Ymd');
        $filename = implode('_', $parts) . '.xlsx';

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Metadata');

        $headers = [
            'ID',
            'Nama',
            'Alias',
            'Konsep',
            'Definisi',
            'Klasifikasi',
            'Asumsi',
            'Metodologi',
            'Penjelasan Metodologi',
            'Tipe Data',
            'Satuan Data',
            'Tahun Mulai Data',
            'Frekuensi Penerbitan',
            'Tahun Pertama Rilis',
            'Bulan Pertama Rilis',
            'Tanggal Rilis',
            'Produsen Id',
            'Tag',
            'Flag Desimal',
            'Tipe Group',
            'Group By',
            'Status',
        ];

        /*
        |--------------------------------------------------------------------------
        | Header Row
        |--------------------------------------------------------------------------
        */

        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $sheet->getColumnDimension($col)->setAutoSize(true);
            $col++;
        }

        $sheet->getStyle('A1:V1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 10,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Data Rows
        |--------------------------------------------------------------------------
        */

        foreach ($rows as $index => $m) {
            $row = $index + 2;

            $sheet->setCellValue("A{$row}", $m->metadata_id);
            $sheet->setCellValue("B{$row}", $m->nama);
            $sheet->setCellValue("C{$row}", $m->alias);
            $sheet->setCellValue("D{$row}", $m->konsep);
            $sheet->setCellValue("E{$row}", $m->definisi);
            $sheet->setCellValue("F{$row}", $m->klasifikasi);
            $sheet->setCellValue("G{$row}", $m->asumsi);
            $sheet->setCellValue("H{$row}", $m->metodologi);
            $sheet->setCellValue("I{$row}", $m->penjelasan_metodologi);
            $sheet->setCellValue("J{$row}", $m->tipe_data);
            $sheet->setCellValue("K{$row}", $m->satuan_data);
            $sheet->setCellValue("L{$row}", $m->tahun_mulai_data);
            $sheet->setCellValue("M{$row}", $m->frekuensi_penerbitan);
            $sheet->setCellValue("N{$row}", $m->tahun_pertama_rilis);
            $sheet->setCellValue("O{$row}", $m->bulan_pertama_rilis);
            $sheet->setCellValue("P{$row}", $m->tanggal_rilis);
            $sheet->setCellValue("Q{$row}", $m->produsen_id);

            // tag harus format JSON string
            $sheet->setCellValue(
                "R{$row}",
                is_array($m->tag)
                    ? json_encode($m->tag, JSON_UNESCAPED_UNICODE)
                    : ($m->tag ?? '[]')
            );

            $sheet->setCellValue("S{$row}", $m->flag_decimal ?? 0);
            $sheet->setCellValue("T{$row}", $m->tipe_group ?? 0);
            $sheet->setCellValue("U{$row}", $m->group_by);
            $sheet->setCellValue("V{$row}", $m->status ?? 1);

            $sheet->getStyle("A{$row}:V{$row}")
                ->getBorders()
                ->getAllBorders()
                ->setBorderStyle(Border::BORDER_HAIR);
        }

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(
            fn () => $writer->save('php://output'),
            $filename,
            [
                'Content-Type' =>
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            ]
        );
    }
    private function buildKodeWilayah($loc)
    {
        return (string) $loc->location_id;
    }

    public function exportTemplate(Request $request)
    {
        $request->validate([
            'produsen_id' => 'required|exists:produsen_data,produsen_id',
            'rentang'     => 'required|in:5-tahun,semester,quarter,bulanan',
            'tahun_awal'  => 'required|integer|min:1990|max:2099',
        ]);
    
        $produsen  = ProdusenData::findOrFail($request->produsen_id);
        $tahunAwal = (int) $request->tahun_awal;
        $rentang   = $request->rentang;
    
        // ── Metadata aktif milik produsen ─────────────────────────────────────
        $metadataList = Metadata::where('status', self::STATUS_ACTIVE)
            ->where('produsen_id', $request->produsen_id)
            ->orderBy('metadata_id')
            ->get(['metadata_id', 'nama', 'flag_desimal']);
    
        // ── Bangun daftar kolom periode ────────────────────────────────────────
        $periodCols = $this->buildPeriodColumns($rentang, $tahunAwal);
    
        // ── Nama file ──────────────────────────────────────────────────────────
        $rentangLabel = [
            '5-tahun'  => '5Tahun',
            'semester' => 'Semester',
            'quarter'  => 'Quarter',
            'bulanan'  => 'Bulanan',
        ][$rentang];
    
        $filename = 'Template_' . str_replace(' ', '_', $produsen->nama_produsen)
                . '_' . $rentangLabel
                . '_' . $tahunAwal . '-' . ($tahunAwal + 4)
                . '_' . now()->format('Ymd')
                . '.xlsx';

        $metadataIds = $metadataList->pluck('metadata_id')->toArray();
    
        $rawData = [];
    
        if (! empty($metadataIds)) {
            $rawData = \Illuminate\Support\Facades\DB::table('data')
                ->join('time',     'data.time_id',     '=', 'time.time_id')
                ->join('location', 'data.location_id', '=', 'location.location_id')
                ->whereIn('data.metadata_id', $metadataIds)
                ->where('data.status', 1)        
                ->whereIn('time.year', $this->extractYearsFromPeriod($rentang, $tahunAwal))
                ->where('time.month',   0)           
                ->where('time.quarter', 0)
                ->select(
                    'data.metadata_id',
                    'data.location_id',
                    'data.number_value',
                    'time.year',
                    'time.quarter',
                    'time.month',
                    'location.location_id',
                    'location.nama_wilayah' 
                )
                ->orderBy('data.metadata_id')
                ->orderBy('location.location_id')
                ->orderBy('time.year')
                ->get();
        }
        $pivot = []; 
    
        foreach ($rawData as $row) {
            $metaId = (int) $row->metadata_id;
            $locId  = (int) $row->location_id;

            if (! isset($pivot[$metaId][$locId])) {
                $pivot[$metaId][$locId] = [
                    'location_row' => $row,
                    'periods'      => [],
                ];
            }
    
            $periodKey = $this->buildPeriodKey($rentang, $row);
    
            if ($periodKey !== null && ! array_key_exists($periodKey, $pivot[$metaId][$locId]['periods'])) {
                $pivot[$metaId][$locId]['periods'][$periodKey] = $row->number_value !== null
                    ? (float) $row->number_value
                    : null;
            }
        }

        $excelRows = [];   
    
        foreach ($metadataList as $meta) {
            $metaId = $meta->metadata_id;
    
            if (! empty($pivot[$metaId])) {
                foreach ($pivot[$metaId] as $locId => $locData) {
                    $namaLokasi = $this->buildLocationName($locData['location_row']);
                    $loc = $locData['location_row'];
                        
                    $row = [
                        'metadata_id'  => $metaId,
                        'nama_metadata'=> $meta->nama,
                        'location_id' => $this->buildKodeWilayah($loc),
                        'nama_wilayah'  => $namaLokasi,
                        'rujukan_id'    => null,
                        'flag_desimal' => $meta->flag_desimal ?? 0,
                        'periods'      => [],
                    ];
    
                    foreach ($periodCols as $colLabel) {
                        $row['periods'][$colLabel] = $locData['periods'][$colLabel] ?? null;
                    }
    
                    $excelRows[] = $row;
                }
            } else {
                    
                $row = [
                    'metadata_id'  => $metaId,
                    'nama_metadata'=> $meta->nama,
                    'location_id' => '',
                    'nama_wilayah'  => null,
                    'rujukan_id'    => null,
                    'flag_desimal' => $meta->flag_desimal ?? 0,
                    'periods'      => array_fill_keys($periodCols, null),
                ];
                $excelRows[] = $row;
            }
        }

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setTitle('Template Import Data')
            ->setDescription('Template import data untuk produsen ' . $produsen->nama_produsen);
    
        // Warna palet 
        $C_HEADER    = '0284C7';   
        $C_PERIOD    = '0369A1';   
        $C_META_FILL = 'E0F2FE';   
        $C_META_ALT  = 'DBEAFE';   
        $C_VAL_ALT   = 'F0F9FF';   
    
        $ws = $spreadsheet->getActiveSheet()->setTitle('Data Import');
    
        $totalCols     = 5 + count($periodCols);
        $lastColLetter = $this->colLetter($totalCols);
    
        // ── Baris 1: Judul ────────────────────────────────────────────────────
        $ws->mergeCells('A1:' . $lastColLetter . '1');
        $ws->setCellValue('A1',
            'Template Import Data — ' . $produsen->nama_produsen
            . ' | ' . $rentangLabel . ' ' . $tahunAwal . '–' . ($tahunAwal + 4)
        );
        $ws->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 13, 'color' => ['rgb' => $C_HEADER]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical'   => Alignment::VERTICAL_CENTER],
        ]);
        $ws->getRowDimension(1)->setRowHeight(26);
    
        // ── Baris 2: Info ─────────────────────────────────────────────────────
        $totalLokasi   = count($excelRows);
        $totalWithData = array_reduce($excelRows, function($c, $r) {
            return $c + (!empty($r['location_id']) ? 1 : 0);
        }, 0);
    
        $ws->mergeCells('A2:' . $lastColLetter . '2');
        $ws->setCellValue('A2',
            $metadataList->count() . ' metadata aktif  |  '
            . count($periodCols) . ' kolom periode  |  '
            . $totalWithData . ' baris berisi data existing'
        );
        $ws->getStyle('A2')->applyFromArray([
            'font'      => ['size' => 9, 'italic' => true, 'color' => ['rgb' => '64748B']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $ws->getRowDimension(2)->setRowHeight(16);
    
        // ── Baris 3: Header kolom ─────────────────────────────────────────────
        $fixedHeaders = [
            ['label' => 'metadata_id',   'width' => 13, 'note' => 'ID metadata (otomatis, jangan diubah)'],
            ['label' => 'nama_metadata', 'width' => 40, 'note' => 'Nama metadata (otomatis, jangan diubah)'],
            ['label' => 'location_id',   'width' => 13, 'note' => 'ID lokasi dari tabel dimensi lokasi'],
            ['label' => 'nama_wilayah',   'width' => 40, 'note' => 'Nama lokasi (referensi, boleh dikosongkan)'],
            ['label' => 'rujukan_id',    'width' => 15, 'note' => 'ID rujukan dari tabel rujukan'],
        ];
    
        foreach ($fixedHeaders as $i => $h) {
            $col  = $this->colLetter($i + 1);
            $cell = $col . '3';
            $ws->setCellValue($cell, $h['label']);
            $ws->getColumnDimension($col)->setWidth($h['width']);
            $ws->getComment($cell)->getText()->createTextRun($h['note']);
        }
    
        foreach ($periodCols as $pi => $periodLabel) {
            $col = $this->colLetter(6 + $pi);
            $ws->setCellValue($col . '3', $periodLabel);
            $ws->getColumnDimension($col)->setWidth(12);
        }
    
        // Style header A–E (sky-600)
        $ws->getStyle('A3:E3')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill'      => ['fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => $C_HEADER]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical'   => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN,
                                            'color'       => ['rgb' => '0369A1']]],
        ]);
    
        if (count($periodCols) > 0) {
            $ws->getStyle('F3:' . $lastColLetter . '3')->applyFromArray([
                'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
                'fill'      => ['fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => $C_PERIOD]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER,
                                'vertical'   => Alignment::VERTICAL_CENTER],
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN,
                                                'color'       => ['rgb' => '075985']]],
            ]);
        }
        $ws->getRowDimension(3)->setRowHeight(22);
    
        // ── Baris 4+: Data ────────────────────────────────────────────────────
        if (empty($excelRows)) {
            $ws->mergeCells('A4:' . $lastColLetter . '4');
            $ws->setCellValue('A4', 'Tidak ada metadata aktif untuk produsen ini.');
            $ws->getStyle('A4')->applyFromArray([
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'font'      => ['italic' => true, 'color' => ['rgb' => '9CA3AF']],
            ]);
        } else {
            foreach ($excelRows as $i => $row) {
                $rowNum = $i + 4;
                $isAlt  = ($i % 2 === 1);
    
                // ── Kolom A: metadata_id ─────────────────────────
                $ws->setCellValue('A' . $rowNum, $row['metadata_id']);
                $ws->getStyle('A' . $rowNum)->applyFromArray([
                    'fill'      => ['fillType'   => Fill::FILL_SOLID,
                                    'startColor' => ['rgb' => $isAlt ? $C_META_ALT : $C_META_FILL]],
                    'font'      => ['bold' => true, 'size' => 9, 'color' => ['rgb' => '0369A1']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER,
                                    'vertical'   => Alignment::VERTICAL_CENTER],
                    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_HAIR,
                                                    'color'       => ['rgb' => 'BFDBFE']]],
                ]);
    
                // ── Kolom B: nama_metadata ───────────────────────
                $ws->setCellValue('B' . $rowNum, $row['nama_metadata']);
                $ws->getStyle('B' . $rowNum)->applyFromArray([
                    'fill'      => ['fillType'   => Fill::FILL_SOLID,
                                    'startColor' => ['rgb' => $isAlt ? $C_META_ALT : $C_META_FILL]],
                    'font'      => ['size' => 9, 'color' => ['rgb' => '0369A1']],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER,
                                    'wrapText' => true],
                    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_HAIR,
                                                    'color'       => ['rgb' => 'BFDBFE']]],
                ]);
    
                // ── Kolom C: location_id ─────────────────────────
                $ws->setCellValue('C' . $rowNum, $row['location_id']);
                $ws->getStyle('C' . $rowNum)->applyFromArray([
                    'fill'      => ['fillType'   => Fill::FILL_SOLID,
                                    'startColor' => ['rgb' => 'FFFFFF']],
                    'font'      => ['size' => 9],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_HAIR,
                                                    'color'       => ['rgb' => 'E2E8F0']]],
                ]);
    
                // ── Kolom D: nama_wilayah ─────────────────────────
                $ws->setCellValue('D' . $rowNum, $row['nama_wilayah']);
                $ws->getStyle('D' . $rowNum)->applyFromArray([
                    'fill'      => ['fillType'   => Fill::FILL_SOLID,
                                    'startColor' => ['rgb' => 'FFFFFF']],
                    'font'      => ['size' => 9],
                    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_HAIR,
                                                    'color'       => ['rgb' => 'E2E8F0']]],
                ]);

                $ws->setCellValue('E' . $rowNum, $row['rujukan_id']);
                $ws->getStyle('E' . $rowNum)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FFFFFF']
                    ],
                    'font' => ['size' => 9],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_HAIR,
                            'color' => ['rgb' => 'E2E8F0']
                        ]
                    ],
                ]);
    
                // ── Kolom E+: nilai per periode ──────────────────
                $flagDesimal = (int) ($row['flag_desimal'] ?? 0);
                $numFormat   = $flagDesimal > 0
                    ? '#,##0.0' . str_repeat('0', $flagDesimal)
                    : '#,##0';
    
                foreach ($periodCols as $pi => $colLabel) {
                    $col   = $this->colLetter(5 + $pi);
                    $value = $row['periods'][$colLabel] ?? null;
    
                    if ($value !== null) {
                        $ws->setCellValue($col . $rowNum, $value);
                        $ws->getStyle($col . $rowNum)->getNumberFormat()->setFormatCode($numFormat);
                    }
    
                    $ws->getStyle($col . $rowNum)->applyFromArray([
                        'fill'      => ['fillType'   => Fill::FILL_SOLID,
                                        'startColor' => ['rgb' => $isAlt ? $C_VAL_ALT : 'FFFFFF']],
                        'font'      => ['size' => 9],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                        'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_HAIR,
                                                        'color'       => ['rgb' => 'E0F2FE']]],
                    ]);
                }
    
                $ws->getRowDimension($rowNum)->setRowHeight(18);
            }
        }
    
        // ── Freeze & AutoFilter ───────────────────────────────────────────────
        $ws->freezePane('F4');
        $ws->setAutoFilter('A3:' . $lastColLetter . '3');
    
        // ── Proteksi: lock kolom A & B, buka C–lastCol untuk edit ────────────
        $excelPassword = env('EXCEL_SHEET_PASSWORD', 'default_password');

        $ws->getProtection()
            ->setSheet(true)
            ->setPassword($excelPassword);
    
        $lastDataRow = max(count($excelRows) + 3, 4);
        $unlockStyle = ['protection' => [
            'locked' => Protection::PROTECTION_UNPROTECTED,
        ]];

        $ws->getStyle('A1:' . $lastColLetter . '3')->applyFromArray($unlockStyle);

        $ws->getStyle('C4:' . $lastColLetter . $lastDataRow)->applyFromArray($unlockStyle);
    
        $spreadsheet->setActiveSheetIndex(0);
    
        $writer = new Xlsx($spreadsheet);
    
        return response()->streamDownload(
            fn() => $writer->save('php://output'),
            $filename,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }
    
    // ═══════════════════════════════════════════════════════════════════════════
    // HELPER BARU: extractYearsFromPeriod
    // ═══════════════════════════════════════════════════════════════════════════
    private function extractYearsFromPeriod(string $rentang, int $tahunAwal): array
    {
        return range($tahunAwal, $tahunAwal + 4);
    }
    
    // ═══════════════════════════════════════════════════════════════════════════
    // HELPER BARU: buildPeriodKey
    // ═══════════════════════════════════════════════════════════════════════════
    private function buildPeriodKey(string $rentang, object $row): ?string
    {
        $year = (int) $row->year;
    
        switch ($rentang) {
            case '5-tahun':
                return (string) $year;
    
            case 'semester':
                $s = $row->quarter <= 2 ? 1 : 2;
                return $year . '_S' . $s;
    
            case 'quarter':
                return $year . '_Q' . $row->quarter;
    
            case 'bulanan':
                $bulanPendek = ['Jan','Feb','Mar','Apr','Mei','Jun',
                                'Jul','Agu','Sep','Okt','Nov','Des'];
                $month = (int) $row->month;
                if ($month < 1 || $month > 12) return null;
                return $bulanPendek[$month - 1] . '_' . $year;
        }
    
        return null;
    }
    
    // ═══════════════════════════════════════════════════════════════════════════
    // HELPER buildLocationName
    // ═══════════════════════════════════════════════════════════════════════════
    private function buildLocationName(object $row): string
    {
        return $row->nama_wilayah ?? '-';
    }
    
    // ═══════════════════════════════════════════════════════════════════════════
    // HELPER hasLevel
    // ═══════════════════════════════════════════════════════════════════════════
    private function hasLevel(?string $kode): bool
    {
        if ($kode === null || $kode === '') {
            return false;
        }
        return ltrim($kode, '0') !== '';
    }

    private function colLetter(int $index): string
    {
        $letter = '';
        while ($index > 0) {
            $index--;
            $letter = chr(65 + ($index % 26)) . $letter;
            $index  = intdiv($index, 26);
        }
        return $letter;
    }

    private function buildPeriodColumns(string $rentang, int $tahunAwal): array
    {
        $cols      = [];
        $bulanPendek = ['Jan','Feb','Mar','Apr','Mei','Jun',
                         'Jul','Agu','Sep','Okt','Nov','Des'];

        for ($y = $tahunAwal; $y < $tahunAwal + 5; $y++) {
            switch ($rentang) {
                case '5-tahun':
                    $cols[] = (string) $y;
                    break;

                case 'semester':
                    $cols[] = $y . '_S1';
                    $cols[] = $y . '_S2';
                    break;

                case 'quarter':
                    for ($q = 1; $q <= 4; $q++) $cols[] = $y . '_Q' . $q;
                    break;

                case 'bulanan':
                    for ($m = 0; $m < 12; $m++) $cols[] = $bulanPendek[$m] . '_' . $y;
                    break;
            }
        }

        return $cols;
    }

    // ═══════════════════════════════════════════════════════════
    // CREATE / STORE / CHECK NAMA / APPROVAL / APPROVE / REJECT
    // REACTIVATE / BULK APPROVE / BULK APPROVE ALL / DETAIL
    // ═══════════════════════════════════════════════════════════
    public function create()
    {
        $metadataList = Metadata::where('status', self::STATUS_ACTIVE)->orderBy('nama')->get();
        $produsen     = ProdusenData::all();
        return view('pages.metadata.create', compact('metadataList', 'produsen'));
    }

    public function store(Request $request)
    {

        $request->validate([
            'nama'                  => ['required','max:100',Rule::unique('metadata','nama')],
            'alias'                 => 'nullable|max:100',
            'konsep'                => 'required',
            'definisi'              => 'required',
            'klasifikasi'           => 'required|max:100',
            'asumsi'                => 'nullable',
            'metodologi'            => 'required|max:100',
            'penjelasan_metodologi' => 'required',
            'tipe_data'             => 'required|max:50',
            'satuan_data'           => 'required|max:50',
            'tahun_mulai_data'      => 'required|max:50',
            'frekuensi_penerbitan'  => 'required|max:50',
            'tahun_pertama_rilis'   => 'nullable|integer|min:1900|max:2100',
            'bulan_pertama_rilis'   => 'nullable|integer|between:1,12',
            'tanggal_rilis'         => 'nullable|integer|between:1,31',
            'flag_desimal'          => 'required|integer',
            'tag'                   => 'required|max:255',
            'produsen_id'           => 'required|exists:produsen_data,produsen_id',
            'tipe_group'            => 'required|integer',
            'group_by'              => [
                'nullable',
                Rule::requiredIf($request->tipe_group == 1),
                Rule::exists('metadata','metadata_id')->where('status', self::STATUS_ACTIVE),
            ],
        ]);

        Metadata::create([
            'nama'                   => $request->nama,
            'alias'                  => $request->alias,
            'konsep'                 => $request->konsep,
            'definisi'               => $request->definisi,
            'klasifikasi'            => $request->klasifikasi,
            'asumsi'                 => $request->filled('asumsi') ? $request->asumsi : null,
            'metodologi'             => $request->metodologi,
            'penjelasan_metodologi'  => $request->penjelasan_metodologi,
            'tipe_data'              => $request->tipe_data,
            'satuan_data'            => $request->satuan_data,
            'tahun_mulai_data'       => $request->tahun_mulai_data,
            'frekuensi_penerbitan'   => $request->frekuensi_penerbitan,
            'tahun_pertama_rilis'    => $request->tahun_pertama_rilis,
            'bulan_pertama_rilis'    => $request->bulan_pertama_rilis,
            'tanggal_rilis'          => $request->tanggal_rilis,
            'flag_desimal'           => $request->flag_desimal,
            'tag'                    => $request->tag,
            'produsen_id'            => $request->produsen_id,
            'tipe_group'             => $request->tipe_group ?? 0,
            'group_by'               => $request->tipe_group == 1 ? $request->group_by : null,
            'status'                 => self::STATUS_PENDING,
            'date_inputed'           => now(),
            'user_id'                => Auth::user()->user_id,
        ]);

        return redirect()->route('metadata.index')
            ->with('success', 'Metadata berhasil ditambahkan dan menunggu persetujuan admin.');
    }

    public function checkNama(Request $request)
    {
        return response()->json(['exists' => Metadata::where('nama', $request->query('nama',''))->exists()]);
    }

    public function approval(Request $request)
    {
        $statusFilter = (int) $request->input('status', self::STATUS_PENDING);
        $query = Metadata::with(['user','produsen'])->where('status', $statusFilter);

        if ($request->filled('search'))             { $query->where('nama','like','%'.$request->search.'%'); }
        if ($request->filled('filter_nama'))        { $query->where('nama','like','%'.$request->filter_nama.'%'); }
        if ($request->filled('filter_klasifikasi')) { $query->where('klasifikasi',$request->filter_klasifikasi); }
        if ($request->filled('filter_produsen_id')) { $query->where('produsen_id',$request->filter_produsen_id); }
        if ($request->filled('filter_tipe_data'))   { $query->where('tipe_data',$request->filter_tipe_data); }
        if ($request->filled('filter_user'))        { $query->whereHas('user',fn($q)=>$q->where('name','like','%'.$request->filter_user.'%')); }
        if ($request->filled('filter_date_from'))   { $query->whereDate('date_inputed','>=',$request->filter_date_from); }
        if ($request->filled('filter_date_to'))     { $query->whereDate('date_inputed','<=',$request->filter_date_to); }

        $data = $query->orderBy('metadata_id','desc')->paginate(15)->withQueryString();

        $countPending  = Metadata::where('status', self::STATUS_PENDING)->count();
        $countActive   = Metadata::where('status', self::STATUS_ACTIVE)->count();
        $countInactive = Metadata::where('status', self::STATUS_INACTIVE)->count();

        $klasifikasiList = Metadata::select('klasifikasi')->distinct()->orderBy('klasifikasi')->pluck('klasifikasi')->filter()->values();
        $tipeDataList    = Metadata::select('tipe_data')->distinct()->orderBy('tipe_data')->pluck('tipe_data')->filter()->values();
        $produsenList    = ProdusenData::whereIn('produsen_id', Metadata::distinct()->pluck('produsen_id'))->orderBy('nama_produsen')->get(['produsen_id','nama_produsen']);

        return view('pages.metadata.approval', compact('data','countPending','countActive','countInactive','statusFilter','klasifikasiList','tipeDataList','produsenList'));
    }

    public function approve(Request $request, Metadata $metadata)
    {
        $metadata->update(['status' => self::STATUS_ACTIVE]);
        if ($request->wantsJson()) return response()->json(['success'=>true,'message'=>"Metadata '{$metadata->nama}' berhasil diaktifkan."]);
        return back()->with('success', "Metadata '{$metadata->nama}' berhasil diaktifkan.");
    }

    public function reject(Request $request, Metadata $metadata)
    {
        $metadata->update(['status' => self::STATUS_INACTIVE]);
        if ($request->wantsJson()) return response()->json(['success'=>true,'message'=>"Metadata '{$metadata->nama}' dinonaktifkan."]);
        return back()->with('success', "Metadata '{$metadata->nama}' dinonaktifkan.");
    }

    public function reactivate(Request $request, Metadata $metadata)
    {
        $metadata->update(['status' => self::STATUS_ACTIVE]);
        if ($request->wantsJson()) return response()->json(['success'=>true,'message'=>"Metadata '{$metadata->nama}' berhasil diaktifkan kembali."]);
        return back()->with('success', "Metadata '{$metadata->nama}' berhasil diaktifkan kembali.");
    }

    public function bulkApprove(Request $request)
    {
        $request->validate(['ids'=>'required|array|min:1','ids.*'=>'integer|exists:metadata,metadata_id']);
        $updated = Metadata::whereIn('metadata_id',$request->ids)->where('status',self::STATUS_PENDING)->update(['status'=>self::STATUS_ACTIVE]);
        return response()->json(['success'=>true,'updated'=>$updated,'message'=>"{$updated} metadata berhasil diapprove."]);
    }

    public function bulkApproveAll(Request $request)
    {
        $updated = Metadata::where('status', self::STATUS_PENDING)->update(['status' => self::STATUS_ACTIVE]);
        return response()->json(['success'=>true,'updated'=>$updated,'message'=>"{$updated} metadata berhasil diapprove semua."]);
    }

    public function detail(Metadata $metadata)
    {
        $metadata->load(['groupParent','groupChildren','user','produsen']);
        return view('pages.metadata.detail', compact('metadata'));
    }
}