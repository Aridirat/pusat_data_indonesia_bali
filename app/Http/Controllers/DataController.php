<?php

namespace App\Http\Controllers;

use App\Models\Data;
use App\Models\Metadata;
use App\Models\Location;
use App\Models\Waktu;
use App\Models\Tampilan;
use App\Models\IsiTampilan;
use App\Imports\DataImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DataController extends Controller
{
    const STATUS_AVAILABLE = 1;
    const STATUS_PENDING   = 0;

    // ═══════════════════════════════════════════════════════════
    // INDEX
    // ═══════════════════════════════════════════════════════════
    public function index(Request $request)
    {
        $hasFilter = $request->hasAny(['metadata_id', 'nama_wilayah', 'year', 'search', 'template_id']);

        if ($request->filled('template_id')) {
            $tampilan = Tampilan::where('tampilan_id', $request->template_id)
                ->where('user_id', Auth::user()->user_id)
                ->first();

            if ($tampilan && $tampilan->filter_params) {
                $fp = $tampilan->filter_params;
                $request->merge(array_filter([
                    'metadata_id' => $fp['metadata_id'] ?? null,
                    'nama_wilayah'   => $fp['nama_wilayah']   ?? null,
                    'year'        => $fp['year']         ?? null,
                ]));
            }
        }

        $data = null;
        if ($hasFilter) {
            $query = Data::with(['metadata', 'location', 'time', 'user'])
                ->where('status', Data::STATUS_AVAILABLE);

            if ($request->filled('metadata_id')) $query->where('metadata_id', $request->metadata_id);

            if ($request->filled('nama_wilayah')) {
                $query->whereHas('location', function ($q) use ($request) {
                    if ($request->filled('nama_wilayah')) $q->where('nama_wilayah', $request->nama_wilayah);
                });
            }

            if ($request->filled('year')) $query->whereHas('time', fn($q) => $q->where('year', $request->year));

            if ($request->filled('search')) {
                $s = $request->search;
                $query->where(fn($q) =>
                    $q->whereHas('metadata', fn($m) => $m->where('nama', 'like', "%$s%"))
                      ->orWhere('text_value', 'like', "%$s%")
                );
            }

            $data = $query->orderBy('date_inputed', 'desc')->paginate(15)->withQueryString();
        }

        $metadataList       = Metadata::where('status', 2)->orderBy('nama')->get(['metadata_id', 'nama']);
        $wilayahList      = Location::select('nama_wilayah')->distinct()->orderBy('nama_wilayah')->pluck('nama_wilayah');
        $availableTemplates = Tampilan::where('user_id', Auth::user()->user_id)->orderBy('tampilan_id', 'desc')->get();
        $pendingCount       = Data::where('status', Data::STATUS_PENDING)->count();

        return view('pages.data.index', compact(
            'data', 'metadataList', 'wilayahList',
            'availableTemplates', 'pendingCount', 'hasFilter'
        ));
    }

    // ═══════════════════════════════════════════════════════════
    // AJAX HELPERS
    // ═══════════════════════════════════════════════════════════
    public function getKecamatan(Request $request)
    {
        $request->validate(['kabupaten' => 'required|string']);
        return response()->json(
            Location::where('kabupaten', $request->kabupaten)
                ->select('kecamatan')->distinct()->orderBy('kecamatan')->pluck('kecamatan')
        );
    }

    public function getDesa(Request $request)
    {
        $request->validate(['kecamatan' => 'required|string']);
        return response()->json(
            Location::where('kecamatan', $request->kecamatan)
                ->select('desa')->distinct()->orderBy('desa')->pluck('desa')
        );
    }

    public function searchMetadata(Request $request)
    {
        $q     = $request->input('q', '');
        $limit = $q === '' ? 200 : 30;
        $query = Metadata::where('status', 2)->orderBy('nama');
        if ($q !== '') $query->where('nama', 'like', "%{$q}%");
        return response()->json($query->limit($limit)->get(['metadata_id', 'nama', 'klasifikasi', 'satuan_data']));
    }

    public function searchYear(Request $request)
    {
        $q     = $request->input('q', '');
        $query = Waktu::select('year')->distinct();
        if ($q !== '') $query->where('year', 'like', "{$q}%");
        return response()->json($query->orderByDesc('year')->pluck('year'));
    }

    // ═══════════════════════════════════════════════════════════
    // CREATE / STORE (manual)
    // ═══════════════════════════════════════════════════════════
    public function create()
    {
        $metadataList = Metadata::select('metadata_id', 'nama', 'tipe_data', 'satuan_data', 'frekuensi_penerbitan')
                                ->orderBy('nama')->get();
        $locationList = Location::select('location_id', 'nama_wilayah')->orderBy('nama_wilayah')->get();
        $timeList     = Waktu::select('time_id', 'decade', 'year', 'month')
                            ->orderBy('decade', 'desc')->orderBy('year', 'desc')->orderBy('month')
                            ->get();

        $timeListJs = $timeList->map(function ($t) {
            return [
                'time_id' => $t->time_id,
                'decade'  => $t->decade,
                'year'    => $t->year,
                'month'   => $t->month,
            ];
        })->values()->toArray();

        return view('pages.data.create', compact('metadataList', 'locationList', 'timeList', 'timeListJs'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'metadata_id'       => 'required|integer|exists:metadata,metadata_id',
            'location_id'       => 'required|integer|exists:location,location_id',
            'time_id'           => 'required|integer|exists:time,time_id',
            'text_value'        => 'nullable|string',
            'number_value'      => 'nullable|numeric',
            'kategori_value'    => 'nullable|integer',
            'other'             => 'nullable|string|max:100',
            'analisis_fenomena' => 'nullable|string',
        ]);

        $duplicate = Data::where('metadata_id', $request->metadata_id)
            ->where('location_id', $request->location_id)
            ->where('time_id',     $request->time_id)
            ->first();

        if ($duplicate) {
            return redirect()->back()->withInput()->with('duplicate_warning', [
                'message'         => 'Data dengan kombinasi Metadata, Lokasi, dan Waktu yang sama sudah ada.',
                'existing_id'     => $duplicate->id,
                'existing_status' => $duplicate->status_label,
            ]);
        }

        Data::create([
            'user_id'           => Auth::user()->user_id,
            'metadata_id'       => $request->metadata_id,
            'location_id'       => $request->location_id,
            'time_id'           => $request->time_id,
            'text_value'        => $request->text_value,
            'number_value'      => $request->number_value,
            'kategori_value'    => $request->kategori_value,
            'other'             => $request->other,
            'analisis_fenomena' => $request->analisis_fenomena,
            'status'            => Data::STATUS_PENDING,
            'date_inputed'      => Carbon::now(),
        ]);

        return redirect()->route('data.index')->with('success', 'Data berhasil disimpan dan menunggu verifikasi admin.');
    }

    // ═══════════════════════════════════════════════════════════
    // IMPORT EXCEL — PREVIEW (AJAX)
    // ─────────────────────────────────────────────────────────
    public function previewExcel(Request $request)
    {
        $request->validate([
            'file_excel' => 'required|file|mimes:xlsx,xls|max:10240',
        ], [
            'file_excel.required' => 'File Excel wajib diupload.',
            'file_excel.mimes'    => 'File harus berformat .xlsx atau .xls.',
            'file_excel.max'      => 'Ukuran file maksimal 10MB.',
        ]);

        try {
            $path   = $request->file('file_excel')->getRealPath();
            $import = new DataImport();
            $result = $import->preview($path);

            return response()->json($result);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membaca file Excel: ' . $e->getMessage(),
            ], 422);
        }
    }

    // ═══════════════════════════════════════════════════════════
    // IMPORT EXCEL — SIMPAN KE DB
    // ─────────────────────────────────────────────────────────
    public function importExcel(Request $request)
    {
        $request->validate([
            'file_excel'      => 'required|file|mimes:xlsx,xls|max:10240',
            'skip_duplicates' => 'nullable|boolean',
        ]);

        try {
            $path   = $request->file('file_excel')->getRealPath();
            $import = new DataImport(
                userId:         Auth::user()->user_id,
                skipDuplicates: $request->boolean('skip_duplicates', true)
            );

            $result = $import->import($path);

            if ($request->wantsJson()) {
                return response()->json(array_merge($result, [
                    'redirect' => route('data.index'),
                ]));
            }

            return redirect()->route('data.index')->with('success', $result['message']);

        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengimpor data: ' . $e->getMessage(),
                ], 500);
            }
            return redirect()->back()->withErrors(['file_excel' => 'Gagal mengimpor: ' . $e->getMessage()]);
        }
    }

    // ═══════════════════════════════════════════════════════════
    // TEMPLATE EXCEL
    // ═══════════════════════════════════════════════════════════
    public function downloadTemplateExcel()
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $headers     = ['A1'=>'metadata_id','B1'=>'location_id','C1'=>'time_id',
                        'D1'=>'number_value'];

        foreach ($headers as $cell => $val) $sheet->setCellValue($cell, $val);
        $sheet->getStyle('A1:H1')->applyFromArray([
            'font' => ['bold'=>true,'color'=>['rgb'=>'FFFFFF']],
            'fill' => ['fillType'=>'solid','startColor'=>['rgb'=>'0284C7']],
            'alignment' => ['horizontal'=>'center'],
        ]);
        $sheet->setCellValue('A2', 1); $sheet->setCellValue('B2', 1); $sheet->setCellValue('C2', 1);
        $sheet->setCellValue('D2', 100.50);

        foreach (range('A','D') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);
        $sheet->setTitle('Template Data');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="template_import_data.xlsx"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }

    // ═══════════════════════════════════════════════════════════
    // APPROVAL
    // ═══════════════════════════════════════════════════════════
    public function approval(Request $request)
    {
        $status = $request->input('status', 0);
        $query  = Data::with(['metadata', 'location', 'time', 'user'])->where('status', $status);
        if ($request->filled('metadata_id')) $query->where('metadata_id', $request->metadata_id);

        $data          = $query->orderBy('date_inputed', 'desc')->paginate(20)->withQueryString();
        $metadataList  = Metadata::select('metadata_id', 'nama')->orderBy('nama')->get();
        $approvedCount = Data::where('status', Data::STATUS_AVAILABLE)->count();
        $rejectedCount = Data::where('status', Data::STATUS_REJECTED)->count();

        return view('pages.data.approval', compact('data', 'metadataList', 'approvedCount', 'rejectedCount'));
    }

    public function bulkApprove(Request $request)
    {
        $request->validate(['ids' => 'required|array|min:1', 'ids.*' => 'integer|exists:data,id']);
        $count = Data::whereIn('id', $request->ids)->where('status', Data::STATUS_PENDING)
                     ->update(['status' => Data::STATUS_AVAILABLE]);
        return redirect()->route('data.approval')->with('success', "{$count} data berhasil disetujui.");
    }

    public function approve(Data $datum)
    {
        $datum->update(['status' => Data::STATUS_AVAILABLE]);
        return redirect()->back()->with('success', "Data #{$datum->id} dari metadata {$datum->metadata->nama} berhasil diverifikasi.");
    }

    public function reject(Data $datum)
    {
        $datum->update(['status' => Data::STATUS_REJECTED]);
        return redirect()->back()->with('success', "Data #{$datum->id} dari metadata {$datum->metadata->nama} ditolak.");
    }

    // ═══════════════════════════════════════════════════════════
    // DETAIL
    // ═══════════════════════════════════════════════════════════
    public function show(Data $datum)
    {
        $datum->load(['metadata', 'location', 'time', 'user']);
        return view('pages.data.show', compact('datum'));
    }

    // ═══════════════════════════════════════════════════════════
    // TEMPLATE TAMPILAN
    // ═══════════════════════════════════════════════════════════
    public function storeTemplate(Request $request)
    {
        $request->validate([
            'nama_tampilan'      => 'required|max:100',
            'filter_metadata_id' => 'nullable|exists:metadata,metadata_id',
            'filter_kabupaten'   => 'nullable|string|max:100',
            'filter_kecamatan'   => 'nullable|string|max:100',
            'filter_desa'        => 'nullable|string|max:100',
            'filter_year'        => 'nullable|integer|min:1900|max:2100',
            'data_ids'           => 'nullable|array',
            'data_ids.*'         => 'exists:data,id',
        ]);

        $filterParams = array_filter([
            'metadata_id' => $request->filter_metadata_id,
            'kabupaten'   => $request->filter_kabupaten,
            'kecamatan'   => $request->filter_kecamatan,
            'desa'        => $request->filter_desa,
            'year'        => $request->filter_year,
        ]);

        $tampilan = Tampilan::create([
            'nama_tampilan' => $request->nama_tampilan,
            'user_id'       => Auth::user()->user_id,
            'filter_params' => $filterParams ?: null,
        ]);

        if ($request->filled('filter_metadata_id')) {
            IsiTampilan::create(['tampilan_id' => $tampilan->tampilan_id, 'metadata_id' => $request->filter_metadata_id]);
        }
        if ($request->filled('data_ids')) {
            $tampilan->dataItems()->sync($request->data_ids);
        }

        return redirect()->route('data.index', ['template_id' => $tampilan->tampilan_id])
            ->with('success', "Template \"{$request->nama_tampilan}\" berhasil disimpan.");
    }

    public function deleteTemplate(Tampilan $tampilan)
    {
        if ($tampilan->user_id !== Auth::user()->user_id) abort(403);
        $tampilan->delete();
        return redirect()->route('data.index')->with('success', 'Template berhasil dihapus.');
    }
}