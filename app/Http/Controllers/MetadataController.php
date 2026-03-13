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
use App\Models\Metadata;
use App\Models\ProdusenData;

class MetadataController extends Controller
{
    const STATUS_PENDING  = 1;
    const STATUS_ACTIVE   = 2;
    const STATUS_INACTIVE = 3;

    // ═══════════════════════════════════════════════════════════
    // INDEX — daftar metadata aktif + filter per kolom
    // ═══════════════════════════════════════════════════════════
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

        // Semua produsen untuk modal export (tidak dibatasi status metadata)
        $produsenAll = ProdusenData::orderBy('nama_produsen')->get(['produsen_id', 'nama_produsen']);

        $pendingCount = Metadata::where('status', self::STATUS_PENDING)->count();

        return view('pages.metadata.index', compact(
            'data',
            'klasifikasiList', 'tipeDataList', 'frekuensiList',
            'produsenList', 'produsenAll',
            'pendingCount'
        ));
    }

    // ═══════════════════════════════════════════════════════════
    // EXPORT COUNT — preview jumlah data sebelum download (AJAX JSON)
    // GET /metadata/export/count?produsen_id=X&frekuensi=Y
    // ═══════════════════════════════════════════════════════════
    public function exportCount(Request $request)
    {
        $query = Metadata::where('status', self::STATUS_ACTIVE);
        if ($request->filled('produsen_id')) { $query->where('produsen_id', $request->produsen_id); }
        if ($request->filled('frekuensi'))   { $query->where('frekuensi_penerbitan', $request->frekuensi); }
        return response()->json(['count' => $query->count()]);
    }

    // ═══════════════════════════════════════════════════════════
    // EXPORT — unduh Excel dengan filter modal
    // GET /metadata/export?produsen_id=X&frekuensi=Y
    // ═══════════════════════════════════════════════════════════
    public function export(Request $request)
    {
        $request->validate([
            'produsen_id' => 'nullable|exists:produsen_data,produsen_id',
            'frekuensi'   => 'nullable|string|max:50',
        ]);

        // Bangun query
        $query = Metadata::with(['produsen', 'user'])
                          ->where('status', self::STATUS_ACTIVE)
                          ->orderBy('klasifikasi')
                          ->orderBy('nama');

        if ($request->filled('produsen_id')) { $query->where('produsen_id', $request->produsen_id); }
        if ($request->filled('frekuensi'))   { $query->where('frekuensi_penerbitan', $request->frekuensi); }

        $rows = $query->get();

        // Nama file dinamis
        $parts = ['Metadata'];
        $produsenLabel = null;
        if ($request->filled('produsen_id')) {
            $p = ProdusenData::find($request->produsen_id);
            if ($p) { $produsenLabel = $p->nama_produsen; $parts[] = str_replace(' ', '_', $p->nama_produsen); }
        }
        if ($request->filled('frekuensi')) { $parts[] = str_replace(' ', '_', $request->frekuensi); }
        $parts[]  = now()->format('Ymd');
        $filename = implode('_', $parts) . '.xlsx';

        // ── Spreadsheet ───────────────────────────────────────
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet()->setTitle('Metadata');

        $COL_HEADER = '0284C7'; // sky-600
        $COL_ALT    = 'F0F9FF'; // sky-50

        $bulanList = ['','Januari','Februari','Maret','April','Mei','Juni',
                      'Juli','Agustus','September','Oktober','November','Desember'];

        // Baris 1: Judul
        $sheet->mergeCells('A1:R1');
        $sheet->setCellValue('A1', 'Data Metadata — ' . now()->translatedFormat('d F Y'));
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold'=>true,'size'=>13,'color'=>['rgb'=>$COL_HEADER]],
            'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(26);

        // Baris 2: Info filter
        $info = 'Total: '.$rows->count().' metadata';
        if ($request->filled('frekuensi'))  $info .= '  |  Frekuensi: '.$request->frekuensi;
        if ($produsenLabel)                 $info .= '  |  Produsen: '.$produsenLabel;
        $sheet->mergeCells('A2:R2');
        $sheet->setCellValue('A2', $info);
        $sheet->getStyle('A2')->applyFromArray([
            'font'      => ['size'=>9,'italic'=>true,'color'=>['rgb'=>'64748B']],
            'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(16);

        // Baris 3: Header tabel
        $headers = [
            'A'=>['No',18=>4],    'B'=>['Nama',18=>38],   'C'=>['Alias',18=>24],
            'D'=>['Klasifikasi',18=>16], 'E'=>['Konsep',18=>35], 'F'=>['Definisi',18=>35],
            'G'=>['Asumsi',18=>25],  'H'=>['Metodologi',18=>20],
            'I'=>['Tipe Data',18=>12], 'J'=>['Satuan',18=>12],
            'K'=>['Tahun Mulai',18=>14], 'L'=>['Frekuensi',18=>14],
            'M'=>['Thn Pertama Rilis',18=>16], 'N'=>['Bln Pertama Rilis',18=>16],
            'O'=>['Tgl Rilis',18=>10], 'P'=>['Produsen',18=>28],
            'Q'=>['Contact Person',18=>24], 'R'=>['Email',18=>28],
        ];
        $widths   = [4,38,24,16,35,35,25,20,12,12,14,14,16,16,10,28,24,28];
        $hLabels  = ['No','Nama Metadata','Alias','Klasifikasi','Konsep','Definisi',
                     'Asumsi','Metodologi','Tipe Data','Satuan Data','Tahun Mulai Data',
                     'Frekuensi Penerbitan','Tahun Pertama Rilis','Bulan Pertama Rilis',
                     'Tanggal Rilis','Produsen Data','Contact Person','Email'];
        $cols     = range('A','R');

        foreach ($cols as $idx => $col) {
            $sheet->setCellValue($col.'3', $hLabels[$idx]);
            $sheet->getColumnDimension($col)->setWidth($widths[$idx]);
        }

        $sheet->getStyle('A3:R3')->applyFromArray([
            'font'      => ['bold'=>true,'color'=>['rgb'=>'FFFFFF'],'size'=>10],
            'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>$COL_HEADER]],
            'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER,
                            'vertical'=>Alignment::VERTICAL_CENTER,'wrapText'=>true],
            'borders'   => ['allBorders'=>['borderStyle'=>Border::BORDER_THIN,
                                           'color'=>['rgb'=>'0369A1']]],
        ]);
        $sheet->getRowDimension(3)->setRowHeight(22);

        // Baris data
        foreach ($rows as $i => $m) {
            $r = $i + 4;
            $sheet->setCellValue("A$r", $i + 1);
            $sheet->setCellValue("B$r", $m->nama);
            $sheet->setCellValue("C$r", $m->alias ?? '-');
            $sheet->setCellValue("D$r", $m->klasifikasi);
            $sheet->setCellValue("E$r", $m->konsep);
            $sheet->setCellValue("F$r", $m->definisi);
            $sheet->setCellValue("G$r", $m->asumsi ?? '-');
            $sheet->setCellValue("H$r", $m->metodologi);
            $sheet->setCellValue("I$r", $m->tipe_data);
            $sheet->setCellValue("J$r", $m->satuan_data);
            $sheet->setCellValue("K$r", $m->tahun_mulai_data);
            $sheet->setCellValue("L$r", $m->frekuensi_penerbitan);
            $sheet->setCellValue("M$r", $m->tahun_pertama_rilis ?? '-');
            $sheet->setCellValue("N$r", $m->bulan_pertama_rilis ? ($bulanList[$m->bulan_pertama_rilis] ?? '-') : '-');
            $sheet->setCellValue("O$r", $m->tanggal_rilis ?? '-');
            $sheet->setCellValue("P$r", $m->produsen?->nama_produsen ?? '-');
            $sheet->setCellValue("Q$r", $m->nama_contact_person);
            $sheet->setCellValue("R$r", $m->email_contact_person);

            $bg = $i % 2 === 0 ? 'FFFFFF' : $COL_ALT;
            $sheet->getStyle("A$r:R$r")->applyFromArray([
                'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['rgb'=>$bg]],
                'font'      => ['size'=>9],
                'alignment' => ['vertical'=>Alignment::VERTICAL_TOP,'wrapText'=>true],
                'borders'   => ['allBorders'=>['borderStyle'=>Border::BORDER_HAIR,'color'=>['rgb'=>'E2E8F0']]],
            ]);
            $sheet->getStyle("A$r")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D$r")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getRowDimension($r)->setRowHeight(38);
        }

        // Jika tidak ada data
        if ($rows->isEmpty()) {
            $sheet->mergeCells('A4:R4');
            $sheet->setCellValue('A4', 'Tidak ada data sesuai filter yang dipilih.');
            $sheet->getStyle('A4')->applyFromArray([
                'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER],
                'font'      => ['italic'=>true,'color'=>['rgb'=>'9CA3AF']],
            ]);
        }

        $sheet->freezePane('A4');
        $sheet->setAutoFilter('A3:R3');

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(
            fn() => $writer->save('php://output'),
            $filename,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    // ═══════════════════════════════════════════════════════════
    // CREATE / STORE / CHECK NAMA / APPROVAL / APPROVE / REJECT
    // REACTIVATE / BULK APPROVE / BULK APPROVE ALL / DETAIL
    // (tidak berubah dari versi sebelumnya)
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
            'nama_contact_person'   => 'required|max:100',
            'nomor_contact_person'  => 'required|max:100',
            'email_contact_person'  => 'required|email|max:100',
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
            'nama_rujukan'           => $request->nama_rujukan,
            'link_rujukan'           => $request->link_rujukan,
            'produsen_id'            => $request->produsen_id,
            'nama_contact_person'    => $request->nama_contact_person,
            'nomor_contact_person'   => $request->nomor_contact_person,
            'email_contact_person'   => $request->email_contact_person,
            'tipe_group'             => $request->tipe_group ?? 2,
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
        $statusFilter = (int) $request->get('status', self::STATUS_PENDING);
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