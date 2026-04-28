<?php

namespace App\Http\Controllers;

use App\Models\Tampilan;
use App\Models\IsiTampilan;
use App\Models\Metadata;
use App\Models\Location;
use App\Models\Data;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TemplateController extends Controller
{
    // ═══════════════════════════════════════════════════════════
    // INDEX — daftar template milik user (hanya untuk yang login)
    // ═══════════════════════════════════════════════════════════

    public function index()
    {
        if (!Auth::check()) {
            return redirect()->route('template.create');
        }

        $templates = Tampilan::where('user_id', Auth::user()->user_id)
            ->withCount('isiTampilan')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('pages.template.index', compact('templates'));
    }

    public function restoreState(Request $request)
    {
        $state = session('wilayah_state', []);
        return response()->json(['success' => true, 'state' => $state]);
    }

    // ═══════════════════════════════════════════════════════════
    // CREATE — form pilih jenis template (PUBLIK)
    // ═══════════════════════════════════════════════════════════

    public function create()
    {
        return view('pages.template.create');
    }

    // ═══════════════════════════════════════════════════════════
    // CREATE METADATA — form template berbasis metadata (PUBLIK)
    // ═══════════════════════════════════════════════════════════

    public function createByMetadata()
    {
        $provinsiList = Location::whereRaw("RIGHT(CAST(location_id AS CHAR), 8) = '00000000'")
            ->orderBy('nama_wilayah')
            ->get(['location_id', 'nama_wilayah']);

        $allMetadata = Metadata::where('status', Metadata::STATUS_ACTIVE)
            ->orderBy('nama')
            ->limit(100)
            ->get(['metadata_id', 'nama', 'klasifikasi', 'satuan_data', 'frekuensi_penerbitan']);

        return view('pages.template.create-metadata', compact('provinsiList', 'allMetadata'));
    }

    // ═══════════════════════════════════════════════════════════
    // CREATE KLASIFIKASI — form template berbasis klasifikasi (PUBLIK)
    // ═══════════════════════════════════════════════════════════

    public function createByKlasifikasi()
    {
        $klasifikasiList = Metadata::where('status', Metadata::STATUS_ACTIVE)
            ->select('klasifikasi')->distinct()->orderBy('klasifikasi')->pluck('klasifikasi');

        // Hanya provinsi untuk cascade awal
        $provinsiList = Location::whereRaw("RIGHT(CAST(location_id AS CHAR), 8) = '00000000'")
            ->orderBy('nama_wilayah')
            ->get(['location_id', 'nama_wilayah']);

        return view('pages.template.create-klasifikasi', compact('klasifikasiList', 'provinsiList'));
    }

    // ═══════════════════════════════════════════════════════════
    // CREATE WILAYAH — form template berbasis wilayah (PUBLIK)
    // ═══════════════════════════════════════════════════════════

    public function createByWilayah()
    {
        $provinsiList = Location::whereRaw("RIGHT(CAST(location_id AS CHAR), 8) = '00000000'")
            ->orderBy('nama_wilayah')
            ->get(['location_id', 'nama_wilayah']);

        return view('pages.template.create-wilayah', compact('provinsiList'));
    }

    // ═══════════════════════════════════════════════════════════
    // AJAX — cari metadata (dengan filter opsional)
    // ═══════════════════════════════════════════════════════════

    public function searchMetadata(Request $request)
    {
        $q           = $request->input('q', '');
        $klasifikasi = $request->input('klasifikasi', '');
        $locationId  = $request->input('location_id', '');

        $query = Metadata::where('status', Metadata::STATUS_ACTIVE)
            ->orderBy('nama');

        if ($q !== '') {
            $query->where('nama', 'like', "%{$q}%");
        }

        if ($klasifikasi !== '') {
            $query->where('klasifikasi', $klasifikasi);
        }

        if ($locationId !== '') {
            $query->whereHas('data', function ($q) use ($locationId) {
                $q->where('location_id', $locationId)
                  ->where('status', Data::STATUS_AVAILABLE);
            });
        }

        $limit = ($q === '' && $klasifikasi === '' && $locationId === '') ? 100 : 50;

        return response()->json(
            $query->limit($limit)->get(['metadata_id', 'nama', 'klasifikasi', 'satuan_data', 'frekuensi_penerbitan'])
        );
    }

    // ═══════════════════════════════════════════════════════════
    // AJAX — ambil metadata preview dengan filter waktu/periode
    // Digunakan oleh Template Metadata
    // ═══════════════════════════════════════════════════════════

    public function fetchMetadataPreview(Request $request)
    {
        $request->validate([
            'metadata_ids'   => 'required|array',
            'metadata_ids.*' => 'integer|exists:metadata,metadata_id',
            'location_ids'   => 'nullable|array',
            'location_ids.*' => 'integer|exists:location,location_id',
            // Filter waktu opsional
            'frekuensi'      => 'nullable|string',
            'year_from'      => 'nullable|integer',
            'year_to'        => 'nullable|integer',
            'period_from'    => 'nullable|integer',
            'period_to'      => 'nullable|integer',
        ]);

        $metadataIds = $request->metadata_ids;
        $locationIds = $request->input('location_ids', []);

        $metadataList = Metadata::whereIn('metadata_id', $metadataIds)
            ->where('status', Metadata::STATUS_ACTIVE)
            ->get(['metadata_id', 'nama', 'klasifikasi', 'satuan_data', 'frekuensi_penerbitan']);

        $grouped = [
            'dekade'   => [],
            'tahunan'  => [],
            'semester' => [],
            'kuartal'  => [],
            'bulanan'  => [],
        ];

        foreach ($metadataList as $m) {
            $freq = strtolower($m->frekuensi_penerbitan);
            if (!isset($grouped[$freq])) continue;

            // Cek apakah ada data dengan filter waktu (jika diberikan)
            $hasData = $this->checkMetadataHasDataInPeriod(
                $m->metadata_id,
                $locationIds,
                $request
            );

            if (!$hasData) continue;

            $item = $m->toArray();

            if (!empty($locationIds)) {
                $item['locations'] = Location::whereIn('location_id', $locationIds)
                    ->select('location_id', 'nama_wilayah')
                    ->get()
                    ->map(fn($l) => [
                        'location_id'  => $l->location_id,
                        'nama_wilayah' => $l->nama_wilayah,
                        'has_children' => $this->hasChildrenWithData($l->location_id, $m->metadata_id),
                    ])->toArray();
            } else {
                $item['locations'] = [];
            }

            $grouped[$freq][] = $item;
        }

        return response()->json([
            'success' => true,
            'grouped' => $grouped,
        ]);
    }

    // ═══════════════════════════════════════════════════════════
    // AJAX — ambil metadata berdasarkan klasifikasi + location + periode
    // ═══════════════════════════════════════════════════════════

    public function fetchByKlasifikasi(Request $request)
    {
        $request->validate([
            'klasifikasi'    => 'required|string',
            'location_ids'   => 'nullable|array',
            'location_ids.*' => 'integer|exists:location,location_id',
            'frekuensi'      => 'nullable|string',
            'year_from'      => 'nullable|integer',
            'year_to'        => 'nullable|integer',
            'period_from'    => 'nullable|integer',
            'period_to'      => 'nullable|integer',
        ]);

        $klasifikasi = $request->klasifikasi;
        $locationIds = array_map('intval', $request->input('location_ids', []));

        $query = Metadata::where('status', Metadata::STATUS_ACTIVE)
            ->where('klasifikasi', $klasifikasi)
            ->orderBy('nama');

        if (!empty($locationIds)) {
            $query->whereHas('data', function ($q) use ($locationIds) {
                $q->whereIn('location_id', $locationIds)
                ->where('status', Data::STATUS_AVAILABLE);
            });
        }

        $metadataList = $query->get([
            'metadata_id',
            'nama',
            'klasifikasi',
            'satuan_data',
            'frekuensi_penerbitan'
        ]);

        $locationMap = !empty($locationIds)
            ? Location::whereIn('location_id', $locationIds)
                ->pluck('nama_wilayah', 'location_id')
            : collect();

        $rows = [];
        $grouped = [
            'dekade'   => [],
            'tahunan'  => [],
            'semester' => [],
            'kuartal'  => [],
            'bulanan'  => [],
        ];

        foreach ($metadataList as $m) {
            $freq = strtolower($m->frekuensi_penerbitan);

            if (!isset($grouped[$freq])) {
                continue;
            }

            // Jika tidak pilih wilayah → tampilkan global
            if (empty($locationIds)) {
                $row = [
                    'metadata_id'          => $m->metadata_id,
                    'nama'                 => $m->nama,
                    'klasifikasi'          => $m->klasifikasi,
                    'satuan_data'          => $m->satuan_data,
                    'frekuensi_penerbitan' => $m->frekuensi_penerbitan,
                    'location_id'          => null,
                    'nama_wilayah'         => 'Semua Wilayah',
                    'has_children'         => false,
                    'depth'                => 0,
                ];

                $rows[] = $row;
                $grouped[$freq][] = $row;
                continue;
            }

            // Jika pilih wilayah → buat metadata × wilayah
            foreach ($locationIds as $locId) {
                $checkQuery = Data::where('metadata_id', $m->metadata_id)
                    ->where('location_id', $locId)
                    ->where('status', Data::STATUS_AVAILABLE);

                $checkQuery = $this->applyTimeFilter($checkQuery, $request);

                if (!$checkQuery->exists()) {
                    continue;
                }

                $row = [
                    'metadata_id'          => $m->metadata_id,
                    'nama'                 => $m->nama,
                    'klasifikasi'          => $m->klasifikasi,
                    'satuan_data'          => $m->satuan_data,
                    'frekuensi_penerbitan' => $m->frekuensi_penerbitan,
                    'location_id'          => $locId,
                    'nama_wilayah'         => $locationMap[$locId] ?? '-',
                    'has_children'         => $this->hasChildrenWithData($locId, $m->metadata_id),
                    'depth'                => 0,
                ];

                $rows[] = $row;
                $grouped[$freq][] = $row;
            }
        }

        return response()->json([
            'success' => true,
            'rows'    => $rows,
            'grouped' => $grouped,
            'total'   => count($rows),
        ]);
    }

    // ═══════════════════════════════════════════════════════════
    // AJAX — ambil metadata berdasarkan wilayah + periode
    // Mengembalikan flat rows: setiap metadata × setiap lokasi
    // yang punya data, beserta flag has_children per baris
    // ═══════════════════════════════════════════════════════════

    public function fetchByWilayah(Request $request)
    {
        $request->validate([
            'location_ids'   => 'required|array|min:1',
            'location_ids.*' => 'integer|exists:location,location_id',
            'frekuensi'      => 'nullable|string',
            'year_from'      => 'nullable|integer',
            'year_to'        => 'nullable|integer',
            'period_from'    => 'nullable|integer',
            'period_to'      => 'nullable|integer',
        ]);

        $locationIds = array_map('intval', $request->location_ids);

        // Bangun query data dengan filter waktu
        $dataQuery = Data::whereIn('location_id', $locationIds)
            ->where('status', Data::STATUS_AVAILABLE);

        $dataQuery = $this->applyTimeFilter($dataQuery, $request);

        $metadataIds = $dataQuery->distinct()->pluck('metadata_id')->toArray();

        if (empty($metadataIds)) {
            return response()->json([
                'success' => true,
                'rows'    => [],
                'grouped' => ['dekade' => [], 'tahunan' => [], 'semester' => [], 'kuartal' => [], 'bulanan' => []],
                'total'   => 0,
            ]);
        }

        $metadataList = Metadata::whereIn('metadata_id', $metadataIds)
            ->where('status', Metadata::STATUS_ACTIVE)
            ->orderBy('nama')
            ->get(['metadata_id', 'nama', 'klasifikasi', 'satuan_data', 'frekuensi_penerbitan']);

        $locationMap = Location::whereIn('location_id', $locationIds)
            ->pluck('nama_wilayah', 'location_id');

        // Bangun flat rows: metadata × lokasi yang benar-benar punya data
        $rows    = [];
        $grouped = ['dekade' => [], 'tahunan' => [], 'semester' => [], 'kuartal' => [], 'bulanan' => []];

        foreach ($metadataList as $m) {
            $freq = strtolower($m->frekuensi_penerbitan);

            foreach ($locationIds as $locId) {
                $checkQuery = Data::where('metadata_id', $m->metadata_id)
                    ->where('location_id', $locId)
                    ->where('status', Data::STATUS_AVAILABLE);

                $checkQuery = $this->applyTimeFilter($checkQuery, $request);

                if (!$checkQuery->exists()) continue;

                $row = [
                    'metadata_id'          => $m->metadata_id,
                    'nama'                 => $m->nama,
                    'klasifikasi'          => $m->klasifikasi,
                    'satuan_data'          => $m->satuan_data,
                    'frekuensi_penerbitan' => $m->frekuensi_penerbitan,
                    'location_id'          => $locId,
                    'nama_wilayah'         => $locationMap[$locId] ?? '-',
                    'has_children'         => $this->hasChildrenWithData($locId, $m->metadata_id),
                    'depth'                => 0,
                ];

                $rows[] = $row;

                if (isset($grouped[$freq])) {
                    $grouped[$freq][] = $row;
                }
            }
        }

        return response()->json([
            'success' => true,
            'rows'    => $rows,
            'grouped' => $grouped,
            'total'   => count($rows),
        ]);
    }

    // ═══════════════════════════════════════════════════════════
    // AJAX — ambil direct children dari satu lokasi untuk satu metadata
    // ═══════════════════════════════════════════════════════════

    public function getChildLocations(Request $request)
    {
        $request->validate([
            'location_id' => 'required|integer',
            'metadata_id' => 'required|integer',
        ]);

        $locationId = (string) $request->location_id;
        $metadataId = (int) $request->metadata_id;

        $childLevel = $this->getDirectChildLevel($locationId);

        if (!$childLevel) {
            return response()->json(['children' => []]);
        }

        // Suffix yang HARUS dimiliki oleh child langsung (bukan turunan lebih dalam)
        // Provinsi  (PP00000000) → child = Kabupaten (PPKK000000): suffix 6 nol, bukan 8 nol
        // Kabupaten (PPKK000000) → child = Kecamatan (PPKKKK0000): suffix 4 nol, bukan 6 nol
        // Kecamatan (PPKKKK0000) → child = Desa (PPKKKKxxxx): tidak ada suffix 4 nol
        $childSuffix = match ($childLevel) {
            'kabupaten' => ['suffix' => '000000', 'not_suffix' => '00000000'],
            'kecamatan' => ['suffix' => '0000',   'not_suffix' => '000000'],
            'desa'      => ['suffix' => null,      'not_suffix' => '0000'],
            default     => null,
        };

        if (!$childSuffix) {
            return response()->json(['children' => []]);
        }

        $prefix = $this->getLocationPrefix($locationId);

        // Cari semua location_id anak langsung yang punya data untuk metadata ini
        $childLocQuery = Location::where('location_id', 'like', $prefix . '%')
            ->where('location_id', '!=', $locationId);

        if ($childSuffix['suffix']) {
            $childLocQuery->whereRaw("RIGHT(CAST(location_id AS CHAR), ?) = ?",
                [strlen($childSuffix['suffix']), $childSuffix['suffix']]);
        }

        $childLocQuery->whereRaw("RIGHT(CAST(location_id AS CHAR), ?) != ?",
            [strlen($childSuffix['not_suffix']), $childSuffix['not_suffix']]);

        $candidateIds = $childLocQuery->pluck('location_id')->toArray();

        if (empty($candidateIds)) {
            return response()->json(['children' => []]);
        }

        // Filter hanya yang benar-benar punya data untuk metadata ini
        $hasDataIds = Data::where('metadata_id', $metadataId)
            ->where('status', Data::STATUS_AVAILABLE)
            ->whereIn('location_id', $candidateIds)
            ->distinct()
            ->pluck('location_id')
            ->toArray();

        if (empty($hasDataIds)) {
            return response()->json(['children' => []]);
        }

        $children = Location::whereIn('location_id', $hasDataIds)
            ->select('location_id', 'nama_wilayah')
            ->orderBy('nama_wilayah')
            ->get()
            ->map(fn($l) => [
                'location_id'  => (int) $l->location_id,
                'nama_wilayah' => $l->nama_wilayah,
                'has_children' => $this->hasChildrenWithData($l->location_id, $metadataId),
            ]);

        return response()->json(['children' => $children]);
    }

    // ═══════════════════════════════════════════════════════════
    // AJAX — Cascade Provinsi
    // ═══════════════════════════════════════════════════════════

    public function getProvinsi(Request $request)
    {
        $q = $request->input('q', '');

        $query = Location::whereRaw("RIGHT(CAST(location_id AS CHAR), 8) = '00000000'")
            ->orderBy('nama_wilayah');

        if ($q) {
            $query->where('nama_wilayah', 'like', "%{$q}%");
        }

        return response()->json(
            $query->get(['location_id', 'nama_wilayah'])
        );
    }

    // ═══════════════════════════════════════════════════════════
    // AJAX — Cascade Kabupaten dari Provinsi
    // ═══════════════════════════════════════════════════════════

    public function getKabupaten(Request $request)
    {
        $request->validate(['provinsi_id' => 'required']);

        $prefix = substr((string) $request->provinsi_id, 0, 2);
        $q = $request->input('q', '');

        $query = Location::query()
            ->where('location_id', 'like', $prefix . '%')
            ->whereRaw("RIGHT(CAST(location_id AS CHAR), 6) = '000000'")
            ->whereRaw("RIGHT(CAST(location_id AS CHAR), 8) != '00000000'")
            ->orderBy('nama_wilayah');

        if ($q) {
            $query->where('nama_wilayah', 'like', "%{$q}%");
        }

        return response()->json(
            $query->get(['location_id', 'nama_wilayah'])
        );
    }

    // ═══════════════════════════════════════════════════════════
    // AJAX — Cascade Kecamatan dari Kabupaten
    // ═══════════════════════════════════════════════════════════

    public function getKecamatan(Request $request)
    {
        $request->validate(['kabupaten_id' => 'required']);

        $prefix = substr((string) $request->kabupaten_id, 0, 4);
        $q = $request->input('q', '');

        $query = Location::query()
            ->where('location_id', 'like', $prefix . '%')
            ->whereRaw("RIGHT(CAST(location_id AS CHAR), 4) = '0000'")
            ->whereRaw("RIGHT(CAST(location_id AS CHAR), 6) != '000000'")
            ->orderBy('nama_wilayah');

        if ($q) {
            $query->where('nama_wilayah', 'like', "%{$q}%");
        }

        return response()->json(
            $query->get(['location_id', 'nama_wilayah'])
        );
    }

    // ═══════════════════════════════════════════════════════════
    // AJAX — Cascade Desa dari Kecamatan
    // ═══════════════════════════════════════════════════════════

    public function getDesa(Request $request)
    {
        $request->validate(['kecamatan_id' => 'required']);

        $prefix = substr((string) $request->kecamatan_id, 0, 6);
        $q = $request->input('q', '');

        $query = Location::query()
            ->where('location_id', 'like', $prefix . '%')
            ->whereRaw("RIGHT(CAST(location_id AS CHAR), 4) != '0000'")
            ->orderBy('nama_wilayah');

        if ($q) {
            $query->where('nama_wilayah', 'like', "%{$q}%");
        }

        return response()->json(
            $query->get(['location_id', 'nama_wilayah'])
        );
    }

    // ═══════════════════════════════════════════════════════════
    // STORE — simpan template ke DB (harus login)
    // atau kembalikan JSON jika guest (disimpan di localStorage)
    // ═══════════════════════════════════════════════════════════

    public function store(Request $request)
    {
        $request->validate([
            'nama_tampilan'  => 'required|string|max:100',
            'jenis_template' => 'required|in:metadata,klasifikasi,wilayah',
            'metadata_ids'   => 'required|array|min:1',
            'metadata_ids.*' => 'integer|exists:metadata,metadata_id',
            'location_ids'   => 'nullable|array',
            'location_ids.*' => 'integer|exists:location,location_id',
            'urutan_by'      => 'nullable|array',
            'urutan_by.*'    => 'in:klasifikasi,wilayah',
        ]);

        // Jika tidak login → kembalikan data untuk disimpan di localStorage
        if (!Auth::check()) {
            $templateData = [
                'nama_tampilan'  => $request->nama_tampilan,
                'jenis_template' => $request->jenis_template,
                'metadata_ids'   => $request->metadata_ids,
                'location_ids'   => $request->input('location_ids', []),
                'urutan_by'      => $request->input('urutan_by', []),
                'created_at'     => now()->toIso8601String(),
            ];

            return response()->json([
                'success'         => true,
                'storage'         => 'local',
                'message'         => "Template \"{$request->nama_tampilan}\" disimpan di browser Anda.",
                'template_data'   => $templateData,
                'redirect'        => route('data.index'),
            ]);
        }

        // User login → simpan ke DB
        $urutanBy = $request->input('urutan_by', []);
        $urutanStr = count($urutanBy) === 2 ? 'keduanya' : ($urutanBy[0] ?? null);

        $filterParams = array_filter([
            'jenis_template' => $request->jenis_template,
            'location_ids'   => $request->input('location_ids', []),
            'urutan_by'      => $urutanStr,
            'klasifikasi'    => $request->input('klasifikasi', null),
        ], fn($v) => $v !== null && $v !== [] && $v !== '');

        $tampilan = Tampilan::create([
            'nama_tampilan' => $request->nama_tampilan,
            'user_id'       => Auth::user()->user_id,
            'filter_params' => $filterParams ?: null,
        ]);

        // Simpan isi template (metadata yang dipilih)
        $order = 1;
        foreach ($request->metadata_ids as $metadataId) {
            IsiTampilan::create([
                'tampilan_id' => $tampilan->tampilan_id,
                'metadata_id' => $metadataId,
            ]);
            $order++;
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success'     => true,
                'storage'     => 'database',
                'message'     => "Template \"{$request->nama_tampilan}\" berhasil disimpan.",
                'tampilan_id' => $tampilan->tampilan_id,
                'redirect'    => route('data.index', ['template_id' => $tampilan->tampilan_id]),
            ]);
        }

        return redirect()
            ->route('data.index', ['template_id' => $tampilan->tampilan_id])
            ->with('success', "Template \"{$request->nama_tampilan}\" berhasil disimpan.");
    }

    // ═══════════════════════════════════════════════════════════════
    // SHOW GRAFIK — halaman visualisasi grafik
    // ═══════════════════════════════════════════════════════════════

    public function showGrafik(Request $request)
    {
        $metadataId = $request->input('metadata_id');
        $locationId = $request->input('location_id');

        $metadata = Metadata::where('metadata_id', $metadataId)
            ->where('status', Metadata::STATUS_ACTIVE)
            ->firstOrFail();

        $location = Location::find($locationId);

        return view('pages.template.grafik', compact('metadata', 'location'));
    }
    // ═══════════════════════════════════════════════════════════
    // DELETE
    // ═══════════════════════════════════════════════════════════

    public function destroy(Tampilan $tampilan)
    {
        if ($tampilan->user_id !== Auth::user()->user_id) {
            abort(403);
        }

        $tampilan->delete();

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Template berhasil dihapus.']);
        }

        return redirect()->route('data.index')->with('success', 'Template berhasil dihapus.');
    }

    // ═══════════════════════════════════════════════════════════
    // SHOW — detail isi template (AJAX untuk panel data index)
    // ═══════════════════════════════════════════════════════════

    public function show(Tampilan $tampilan)
    {
        if ($tampilan->user_id !== Auth::user()->user_id) {
            abort(403);
        }

        $tampilan->load('isiTampilan.metadata');

        $metadataIds = $tampilan->isiTampilan->pluck('metadata_id')->toArray();
        $fp          = $tampilan->filter_params ?? [];
        $locationIds = $fp['location_ids'] ?? [];

        $metadataList = Metadata::whereIn('metadata_id', $metadataIds)
            ->where('status', Metadata::STATUS_ACTIVE)
            ->orderBy('nama')
            ->get(['metadata_id', 'nama', 'klasifikasi', 'satuan_data', 'frekuensi_penerbitan']);

        $grouped = [
            'dekade'   => [],
            'tahunan'  => [],
            'semester' => [],
            'kuartal'  => [],
            'bulanan'  => [],
        ];

        foreach ($metadataList as $m) {
            $freq = strtolower($m->frekuensi_penerbitan);
            if (!isset($grouped[$freq])) continue;

            $item = $m->toArray();
            $item['locations'] = !empty($locationIds)
                ? Location::whereIn('location_id', $locationIds)
                    ->select('location_id', 'nama_wilayah')
                    ->get()
                    ->map(fn($l) => [
                        'location_id'  => $l->location_id,
                        'nama_wilayah' => $l->nama_wilayah,
                        'has_children' => $this->hasChildrenWithData($l->location_id, $m->metadata_id),
                    ])->toArray()
                : [];

            $grouped[$freq][] = $item;
        }

        return response()->json([
            'success'       => true,
            'tampilan_id'   => $tampilan->tampilan_id,
            'nama_tampilan' => $tampilan->nama_tampilan,
            'filter_params' => $fp,
            'grouped'       => $grouped,
        ]);
    }

    // ═══════════════════════════════════════════════════════════
    // AJAX — ambil data aktual dengan filter waktu
    // ═══════════════════════════════════════════════════════════

    public function fetchData(Request $request)
    {
        $request->validate([
            'metadata_id'  => 'required|integer|exists:metadata,metadata_id',
            'location_id'  => 'required|integer|exists:location,location_id',
            'frekuensi'    => 'required|string',
            'year_from'    => 'nullable|integer',
            'year_to'      => 'nullable|integer',
            'period_from'  => 'nullable|integer',
            'period_to'    => 'nullable|integer',
        ]);

        $query = Data::with(['time', 'location'])
            ->where('metadata_id', $request->metadata_id)
            ->where('location_id', $request->location_id)
            ->where('status', Data::STATUS_AVAILABLE);

        $query = $this->applyTimeFilter($query, $request);

        $data = $query->with('time')
            ->orderByDesc('date_inputed')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data'    => $data->items(),
            'total'   => $data->total(),
            'pages'   => $data->lastPage(),
            'current' => $data->currentPage(),
        ]);
    }

    // ═══════════════════════════════════════════════════════════
    // PRIVATE HELPERS
    // ═══════════════════════════════════════════════════════════

    /**
     * Terapkan filter waktu ke query berdasarkan frekuensi.
     * Mendukung dekade, tahunan, semester, kuartal, bulanan.
     */
    private function applyTimeFilter($query, Request $request)
    {
        $frekuensi  = strtolower($request->input('frekuensi', ''));
        $yearFrom   = $request->input('year_from');
        $yearTo     = $request->input('year_to');
        $periodFrom = $request->input('period_from');
        $periodTo   = $request->input('period_to');

        if (!$frekuensi) return $query;

        if ($frekuensi === 'dekade') {
            if ($periodFrom) $query->whereHas('time', fn($q) => $q->where('decade', '>=', $periodFrom));
            if ($periodTo)   $query->whereHas('time', fn($q) => $q->where('decade', '<=', $periodTo));
        } elseif ($frekuensi === 'tahunan') {
            if ($periodFrom) $query->whereHas('time', fn($q) => $q->where('year', '>=', $periodFrom));
            if ($periodTo)   $query->whereHas('time', fn($q) => $q->where('year', '<=', $periodTo));
        } elseif ($frekuensi === 'semester') {
            if ($yearFrom)   $query->whereHas('time', fn($q) => $q->where('year', '>=', $yearFrom));
            if ($yearTo)     $query->whereHas('time', fn($q) => $q->where('year', '<=', $yearTo));
            if ($periodFrom) $query->whereHas('time', fn($q) => $q->where('semester', '>=', $periodFrom));
            if ($periodTo)   $query->whereHas('time', fn($q) => $q->where('semester', '<=', $periodTo));
        } elseif ($frekuensi === 'kuartal') {
            if ($yearFrom)   $query->whereHas('time', fn($q) => $q->where('year', '>=', $yearFrom));
            if ($yearTo)     $query->whereHas('time', fn($q) => $q->where('year', '<=', $yearTo));
            if ($periodFrom) $query->whereHas('time', fn($q) => $q->where('quarter', '>=', $periodFrom));
            if ($periodTo)   $query->whereHas('time', fn($q) => $q->where('quarter', '<=', $periodTo));
        } elseif ($frekuensi === 'bulanan') {
            if ($yearFrom)   $query->whereHas('time', fn($q) => $q->where('year', '>=', $yearFrom));
            if ($yearTo)     $query->whereHas('time', fn($q) => $q->where('year', '<=', $yearTo));
            if ($periodFrom) $query->whereHas('time', fn($q) => $q->where('month', '>=', $periodFrom));
            if ($periodTo)   $query->whereHas('time', fn($q) => $q->where('month', '<=', $periodTo));
        }

        return $query;
    }

    /**
     * Cek apakah metadata punya data dalam rentang periode yang diminta.
     * Jika tidak ada filter periode, return true (tampilkan semua).
     */
    private function checkMetadataHasDataInPeriod(int $metadataId, array $locationIds, Request $request): bool
    {
        $frekuensi  = strtolower($request->input('frekuensi', ''));
        $yearFrom   = $request->input('year_from');
        $yearTo     = $request->input('year_to');
        $periodFrom = $request->input('period_from');
        $periodTo   = $request->input('period_to');

        // Jika tidak ada filter waktu sama sekali, tampilkan semua
        $hasTimeFilter = $frekuensi && ($yearFrom || $yearTo || $periodFrom || $periodTo);
        if (!$hasTimeFilter) return true;

        $query = Data::where('metadata_id', $metadataId)
            ->where('status', Data::STATUS_AVAILABLE);

        if (!empty($locationIds)) {
            $query->whereIn('location_id', $locationIds);
        }

        $query = $this->applyTimeFilter($query, $request);

        return $query->exists();
    }

    private function hasChildrenWithData(int $locationId, int $metadataId): bool
    {
        $locStr     = (string) $locationId;
        $childLevel = $this->getDirectChildLevel($locStr);

        if (!$childLevel) return false;

        $childSuffix = match ($childLevel) {
            'kabupaten' => ['suffix' => '000000', 'not_suffix' => '00000000'],
            'kecamatan' => ['suffix' => '0000',   'not_suffix' => '000000'],
            'desa'      => ['suffix' => null,      'not_suffix' => '0000'],
            default     => null,
        };

        if (!$childSuffix) return false;

        $prefix = $this->getLocationPrefix($locStr);

        $childLocQuery = Location::where('location_id', 'like', $prefix . '%')
            ->where('location_id', '!=', $locStr);

        if ($childSuffix['suffix']) {
            $childLocQuery->whereRaw("RIGHT(CAST(location_id AS CHAR), ?) = ?",
                [strlen($childSuffix['suffix']), $childSuffix['suffix']]);
        }

        $childLocQuery->whereRaw("RIGHT(CAST(location_id AS CHAR), ?) != ?",
            [strlen($childSuffix['not_suffix']), $childSuffix['not_suffix']]);

        $candidateIds = $childLocQuery->pluck('location_id')->toArray();

        if (empty($candidateIds)) return false;

        return Data::where('metadata_id', $metadataId)
            ->where('status', Data::STATUS_AVAILABLE)
            ->whereIn('location_id', $candidateIds)
            ->exists();
    }

    /**
     * Menentukan level anak langsung dari suatu location_id.
     * Format location_id 10 digit: PP KK KKK DDD (misal 5102130005)
     *   Provinsi  (PP00000000) → Kabupaten
     *   Kabupaten (PPKK000000) → Kecamatan
     *   Kecamatan (PPKKKCC0000) → Desa — di sini kita pakai string length
     *
     * Karena location_id adalah bigint, kita cast ke string untuk cek suffix.
     */
    private function getDirectChildLevel(string $locationId): ?string
    {
        $len = strlen($locationId);

        // Provinsi: 10 digit, 8 digit terakhir = '00000000'
        if ($len >= 8 && substr($locationId, -8) === '00000000') {
            return 'kabupaten';
        }

        // Kabupaten: 6 digit terakhir = '000000', bukan 8 nol
        if ($len >= 6 && substr($locationId, -6) === '000000') {
            return 'kecamatan';
        }

        // Kecamatan: 4 digit terakhir = '0000', bukan 6 nol
        if ($len >= 4 && substr($locationId, -4) === '0000') {
            return 'desa';
        }

        return null; // Desa, tidak ada anak
    }

    /**
     * Panjang string location_id untuk level anak:
     *   kabupaten → 10 digit (sama dengan provinsi)  ← PPKK000000
     *   kecamatan → 10 digit                          ← PPKKKCC0000
     *   desa      → 10 digit                          ← PPKKKCCNNN
     *
     * Sebenarnya semua 10 digit, yang membedakan adalah suffix zero-nya.
     * Kita gunakan pendekatan prefix match + suffix check.
     */
    private function getChildLevelLength(string $level): int
    {
        return 10; // Semua location_id 10 digit
    }

    /**
     * Dapatkan prefix yang benar untuk mencari anak lokasi.
     */
    private function getLocationPrefix(string $locationId): string
    {
        $childLevel = $this->getDirectChildLevel($locationId);

        return match ($childLevel) {
            'kabupaten' => substr($locationId, 0, 2),  // PP
            'kecamatan' => substr($locationId, 0, 4),  // PPKK
            'desa'      => substr($locationId, 0, 6),  // PPKKKK (atau sesuai format)
            default     => $locationId,
        };
    }
}