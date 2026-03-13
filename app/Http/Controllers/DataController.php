<?php

namespace App\Http\Controllers;

use App\Models\Data;
use App\Models\Metadata;
use App\Models\Location;
use App\Models\Waktu;
use App\Models\Tampilan;
use App\Models\IsiTampilan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
// use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\DataImport;
use Carbon\Carbon;

class DataController extends Controller
{
    const STATUS_AVAILABLE = 1;
    const STATUS_PENDING   = 0;
    // ═══════════════════════════════════════════════════════════
    // INDEX — Halaman utama data (hanya status available)
    // ═══════════════════════════════════════════════════════════
    public function index(Request $request)
    {
        $hasFilter = $request->hasAny(['metadata_id', 'kabupaten', 'kecamatan', 'desa', 'year', 'search', 'template_id']);

        // ── Jika ada template dipilih, inject filter_params ke request ──
        if ($request->filled('template_id')) {
            $tampilan = Tampilan::where('tampilan_id', $request->template_id)
                ->where('user_id', Auth::user()->user_id)
                ->first();

            if ($tampilan && $tampilan->filter_params) {
                $fp = $tampilan->filter_params;
                $request->merge(array_filter([
                    'metadata_id' => $fp['metadata_id'] ?? null,
                    'kabupaten'   => $fp['kabupaten']   ?? null,
                    'kecamatan'   => $fp['kecamatan']   ?? null,
                    'desa'        => $fp['desa']         ?? null,
                    'year'        => $fp['year']         ?? null,
                ]));
            }
        }

        // ── Hanya query data jika ada filter aktif ──
        
        $data = null;
        if ($hasFilter) {
            $query = Data::with(['metadata', 'location', 'time', 'user'])
                ->where('status', Data::STATUS_AVAILABLE);

            if ($request->filled('metadata_id')) {
                $query->where('metadata_id', $request->metadata_id);
            }

            if ($request->filled('kabupaten') || $request->filled('kecamatan') || $request->filled('desa')) {
                $query->whereHas('location', function ($q) use ($request) {
                    if ($request->filled('kabupaten')) $q->where('kabupaten', $request->kabupaten);
                    if ($request->filled('kecamatan')) $q->where('kecamatan', $request->kecamatan);
                    if ($request->filled('desa'))      $q->where('desa',      $request->desa);
                });
            }

            if ($request->filled('year')) {
                $query->whereHas('time', fn($q) => $q->where('year', $request->year));
            }

            if ($request->filled('search')) {
                $s = $request->search;
                $query->where(function ($q) use ($s) {
                    $q->whereHas('metadata', fn($m) => $m->where('nama', 'like', "%$s%"))
                    ->orWhere('text_value', 'like', "%$s%");
                });
            }

            $data = $query->orderBy('date_inputed', 'desc')->paginate(15)->withQueryString();
        }

        // ── Dropdown data (ringan, selalu diload) ──
        $metadataList = Metadata::where('status', 2)->orderBy('nama')->get(['metadata_id', 'nama']);
        $kabupatenList = Location::select('kabupaten')->distinct()->orderBy('kabupaten')->pluck('kabupaten');

        // ── Template milik user ──
        $availableTemplates = Tampilan::where('user_id', Auth::user()->user_id)
            ->orderBy('tampilan_id', 'desc')
            ->get();

        $pendingCount = Data::where('status', Data::STATUS_PENDING)->count();

        return view('pages.data.index', compact(
            'data', 'metadataList', 'kabupatenList',
            'availableTemplates', 'pendingCount', 'hasFilter'
        ));
    }

    public function getKecamatan(Request $request)
    {
        $request->validate(['kabupaten' => 'required|string']);

        $list = Location::where('kabupaten', $request->kabupaten)
            ->select('kecamatan')
            ->distinct()
            ->orderBy('kecamatan')
            ->pluck('kecamatan');

        return response()->json($list);
    }

    public function getDesa(Request $request)
    {
        $request->validate(['kecamatan' => 'required|string']);

        $list = Location::where('kecamatan', $request->kecamatan)
            ->select('desa')
            ->distinct()
            ->orderBy('desa')
            ->pluck('desa');

        return response()->json($list);
    }

    public function searchMetadata(Request $request)
    {
        $q     = $request->get('q', '');
        // Saat q kosong (fokus pertama), kembalikan semua metadata yang aktif.
        // Batasi 200 agar tidak terlalu berat; sesuaikan dengan kebutuhan.
        $limit = $q === '' ? 200 : 30;

        $query = Metadata::where('status', 2)->orderBy('nama');

        if ($q !== '') {
            $query->where('nama', 'like', "%{$q}%");
        }

        $results = $query->limit($limit)
            ->get(['metadata_id', 'nama', 'klasifikasi', 'satuan_data']);

        return response()->json($results);
    }

    public function searchYear(Request $request)
    {
        $q = $request->get('q', '');

        // Ambil semua tahun unik dari tabel time/waktu.
        // Ganti `DataTime` dengan nama model yang Anda gunakan
        // (bisa juga `Time`, `DataWaktu`, dll.).
        $query = Waktu::select('year')->distinct();

        if ($q !== '') {
            $query->where('year', 'like', "{$q}%"); // prefix match lebih relevan untuk tahun
        }

        $years = $query->orderByDesc('year')->pluck('year');

        return response()->json($years);
    }

    // ═══════════════════════════════════════════════════════════
    // CREATE — Form input data (manual & excel)
    // ═══════════════════════════════════════════════════════════
    public function create()
    {
        $metadataList = Metadata::select('metadata_id', 'nama', 'tipe_data', 'satuan_data')
                            ->orderBy('nama')->get();
        $locationList = Location::select('location_id', 'provinsi', 'kabupaten', 'kecamatan', 'desa')
                            ->orderBy('kabupaten')->get();
        $timeList     = Waktu::select('time_id', 'year', 'month', 'day')
                            ->orderBy('year', 'desc')
                            ->orderBy('month')
                            ->orderBy('day')
                            ->get();

        return view('pages.data.create', compact('metadataList', 'locationList', 'timeList'));
    }

    // ═══════════════════════════════════════════════════════════
    // STORE — Simpan data manual
    // ═══════════════════════════════════════════════════════════
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
        ], [
            'metadata_id.required' => 'Metadata wajib dipilih.',
            'metadata_id.exists'   => 'Metadata tidak ditemukan.',
            'location_id.required' => 'Lokasi wajib dipilih.',
            'location_id.exists'   => 'Lokasi tidak ditemukan.',
            'time_id.required'     => 'Waktu wajib dipilih.',
            'time_id.exists'       => 'Data waktu tidak ditemukan.',
        ]);

        // ── Cek duplikat ──
        $duplicate = Data::where('metadata_id', $request->metadata_id)
            ->where('location_id',  $request->location_id)
            ->where('time_id',      $request->time_id)
            ->first();

        if ($duplicate) {
            return redirect()
                ->back()
                ->withInput()
                ->with('duplicate_warning', [
                    'message'      => 'Data dengan kombinasi Metadata, Lokasi, dan Waktu yang sama sudah ada.',
                    'existing_id'  => $duplicate->id,
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

        return redirect()
            ->route('data.index')
            ->with('success', 'Data berhasil disimpan dan menunggu verifikasi admin.');
    }

    // ═══════════════════════════════════════════════════════════
    // IMPORT EXCEL — Preview & simpan dari file Excel
    // ═══════════════════════════════════════════════════════════

    public function previewExcel(Request $request)
    {
        $request->validate([
            'file_excel' => 'required|file|mimes:xlsx,xls|max:5120',
        ], [
            'file_excel.required' => 'File Excel wajib diupload.',
            'file_excel.mimes'    => 'File harus berformat .xlsx atau .xls.',
            'file_excel.max'      => 'Ukuran file maksimal 5MB.',
        ]);

        try {
            $import = new DataImport();
            Excel::import($import, $request->file('file_excel'));

            $rows      = $import->getRows();      // semua baris dari Excel
            $errors    = $import->getErrors();    // baris yang tidak sesuai format
            $duplicates = $import->getDuplicates(); // baris yang sudah ada di DB

            return response()->json([
                'success'    => true,
                'rows'       => $rows,
                'errors'     => $errors,
                'duplicates' => $duplicates,
                'total'      => count($rows),
                'valid'      => count($rows) - count($errors) - count($duplicates),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membaca file Excel: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Simpan data dari Excel setelah dikonfirmasi user
     */
    public function importExcel(Request $request)
    {
        $request->validate([
            'file_excel'   => 'required|file|mimes:xlsx,xls|max:5120',
            'skip_duplicates' => 'nullable|boolean',
        ]);

        try {
            $import = new DataImport(
                userId: Auth::user()->user_id,
                skipDuplicates: $request->boolean('skip_duplicates', true)
            );

            Excel::import($import, $request->file('file_excel'));

            $imported   = $import->getImportedCount();
            $skipped    = $import->getSkippedCount();
            $errorCount = count($import->getErrors());

            $message = "Berhasil mengimpor {$imported} data.";
            if ($skipped > 0)    $message .= " {$skipped} data duplikat dilewati.";
            if ($errorCount > 0) $message .= " {$errorCount} data gagal karena format tidak sesuai.";

            return redirect()
                ->route('data.index')
                ->with('success', $message);
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withErrors(['file_excel' => 'Gagal mengimpor: ' . $e->getMessage()]);
        }
    }

    // ═══════════════════════════════════════════════════════════
    // APPROVAL — Admin verifikasi data
    // ═══════════════════════════════════════════════════════════
    public function approval(Request $request)
    {
        $status = $request->get('status', 0); // default: tampilkan pending

        $query = Data::with(['metadata', 'location', 'time', 'user'])
            ->where('status', $status);

        if ($request->filled('metadata_id')) {
            $query->where('metadata_id', $request->metadata_id);
        }

        $data = $query->orderBy('date_inputed', 'desc')->paginate(20)->withQueryString();

        $metadataList  = Metadata::select('metadata_id', 'nama')->orderBy('nama')->get();
        $approvedCount = Data::where('status', Data::STATUS_AVAILABLE)->count();
        $rejectedCount = Data::where('status', Data::STATUS_REJECTED)->count();

        return view('pages.data.approval', compact(
            'data', 'metadataList', 'approvedCount', 'rejectedCount'
        ));
    }


    // ═══════════════════════════════════════════════════════════════
    // TAMBAHKAN method baru bulkApprove() di DataController
    // ═══════════════════════════════════════════════════════════════

    /**
     * Setujui banyak data sekaligus
     */
    public function bulkApprove(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer|exists:data,id',
        ]);

        $count = Data::whereIn('id', $request->ids)
            ->where('status', Data::STATUS_PENDING)
            ->update(['status' => Data::STATUS_AVAILABLE]);

        return redirect()
            ->route('data.approval')
            ->with('success', "{$count} data berhasil disetujui.");
    }

    public function approve(Data $datum)
    {
        $datum->update(['status' => Data::STATUS_AVAILABLE]);

        return redirect()
            ->back()
            ->with('success', "Data #$datum->id berhasil diverifikasi dan sekarang tersedia.");
    }

    public function reject(Data $datum)
    {
        $datum->update(['status' => Data::STATUS_REJECTED]);

        return redirect()
            ->back()
            ->with('success', "Data #$datum->id ditolak.");
    }

    // ═══════════════════════════════════════════════════════════
    // DETAIL
    // ═══════════════════════════════════════════════════════════
    public function show(Data $datum)
    {
        $datum->load(['metadata', 'location', 'time', 'user']);
        return view('pages.data.show', compact('datum'));
    }

    /**
     * Simpan template tampilan baru milik user
     */
    // public function storeTemplate(Request $request)
    // {
    //     $request->validate([
    //         'nama_tampilan' => 'required|string|max:100',
    //         'metadata_ids'  => 'required|array|min:1',
    //         'metadata_ids.*'=> 'integer|exists:metadata,metadata_id',
    //     ], [
    //         'nama_tampilan.required' => 'Nama template wajib diisi.',
    //         'metadata_ids.required'  => 'Pilih minimal satu metadata.',
    //     ]);

    //     // Buat tampilan baru
    //     $tampilan = Tampilan::create([
    //         'nama_tampilan' => $request->nama_tampilan,
    //         'user_id'       => Auth::user()->user_id,
    //     ]);

    //     // Simpan isi tampilan (metadata yang dipilih)
    //     foreach ($request->metadata_ids as $metaId) {
    //         IsiTampilan::create([
    //             'tampilan_id' => $tampilan->tampilan_id,
    //             'metadata_id' => $metaId,
    //         ]);
    //     }

    //     return redirect()
    //         ->route('data.index', ['template_id' => $tampilan->tampilan_id])
    //         ->with('success', "Template '{$request->nama_tampilan}' berhasil disimpan.");
    // }

    public function storeTemplate(Request $request)
    {
        $request->validate([
            'nama_tampilan'  => 'required|max:100',
            // filter params (semua opsional, tapi minimal 1 harus ada)
            'filter_metadata_id' => 'nullable|exists:metadata,metadata_id',
            'filter_kabupaten'   => 'nullable|string|max:100',
            'filter_kecamatan'   => 'nullable|string|max:100',
            'filter_desa'        => 'nullable|string|max:100',
            'filter_year'        => 'nullable|integer|min:1900|max:2100',
            // data yang dipilih (opsional)
            'data_ids'           => 'nullable|array',
            'data_ids.*'         => 'exists:data,id',
        ], [
            'nama_tampilan.required' => 'Nama template wajib diisi.',
        ]);

        // Kumpulkan parameter filter yang diisi
        $filterParams = array_filter([
            'metadata_id' => $request->filter_metadata_id,
            'kabupaten'   => $request->filter_kabupaten,
            'kecamatan'   => $request->filter_kecamatan,
            'desa'        => $request->filter_desa,
            'year'        => $request->filter_year,
        ]);

        // Buat tampilan
        $tampilan = Tampilan::create([
            'nama_tampilan' => $request->nama_tampilan,
            'user_id'       => Auth::user()->user_id,
            'filter_params' => $filterParams ?: null,
        ]);

        // Simpan metadata yang diikutkan (dari filter_metadata_id)
        if ($request->filled('filter_metadata_id')) {
            IsiTampilan::create([
                'tampilan_id' => $tampilan->tampilan_id,
                'metadata_id' => $request->filter_metadata_id,
            ]);
        }

        // Simpan data yang dipilih ke pivot table
        if ($request->filled('data_ids')) {
            $tampilan->dataItems()->sync($request->data_ids);
        }

        return redirect()
            ->route('data.index', ['template_id' => $tampilan->tampilan_id])
            ->with('success', "Template \"{$request->nama_tampilan}\" berhasil disimpan.");
    }

    public function downloadTemplateExcel()
    {
        // Buat file Excel template dengan header kolom yang benar
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();

        // Header kolom
        $headers = [
            'A1' => 'metadata_id',
            'B1' => 'location_id',
            'C1' => 'time_id',
            'D1' => 'number_value',
            'E1' => 'text_value',
            'F1' => 'kategori_value',
            'G1' => 'other',
            'H1' => 'analisis_fenomena',
        ];

        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        // Styling header
        $headerStyle = [
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => 'solid', 'startColor' => ['rgb' => '0284C7']],
            'alignment' => ['horizontal' => 'center'],
        ];
        $sheet->getStyle('A1:H1')->applyFromArray($headerStyle);

        // Contoh baris data
        $sheet->setCellValue('A2', 1);
        $sheet->setCellValue('B2', 1);
        $sheet->setCellValue('C2', 1);
        $sheet->setCellValue('D2', 100.50);
        $sheet->setCellValue('E2', 'Contoh nilai teks');
        $sheet->setCellValue('F2', '');
        $sheet->setCellValue('G2', 'Keterangan');
        $sheet->setCellValue('H2', 'Analisis fenomena contoh');

        // Auto width
        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet->setTitle('Template Data');

        // Output sebagai file download
        $writer   = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'template_import_data.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    public function deleteTemplate(Tampilan $tampilan)
    {
        if ($tampilan->user_id !== Auth::user()->user_id) {
            abort(403);
        }
        $tampilan->delete();
        return redirect()->route('data.index')->with('success', 'Template berhasil dihapus.');
    }
}