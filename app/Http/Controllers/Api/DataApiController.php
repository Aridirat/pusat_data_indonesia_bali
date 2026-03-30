<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Data;
use App\Models\Tampilan;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DataApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Data::with(['metadata', 'location', 'time'])
            ->where('status', Data::STATUS_AVAILABLE);

        if ($request->filled('metadata_id')) {
            $query->where('metadata_id', $request->metadata_id);
        }
        if ($request->filled('location_id')) {
            $query->where('location_id', $request->location_id);
        }
        if ($request->filled('year')) {
            $query->whereHas('time', fn($q) => $q->where('year', $request->year));
        }
        if ($request->filled('month')) {
            $query->whereHas('time', fn($q) => $q->where('month', $request->month));
        }

        $perPage = min((int) $request->get('per_page', 25), 100);
        $result  = $query->orderBy('date_inputed', 'desc')->paginate($perPage);

        return response()->json([
            'status'  => 'success',
            'meta'    => [
                'total'        => $result->total(),
                'per_page'     => $result->perPage(),
                'current_page' => $result->currentPage(),
                'last_page'    => $result->lastPage(),
            ],
            'data' => $result->map(fn($row) => self::formatRow($row)),
        ]);
    }

    public function show($id): JsonResponse
    {
        $row = Data::with(['metadata', 'location', 'time', 'user'])
            ->where('status', Data::STATUS_AVAILABLE)
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data'   => self::formatRow($row, true),
        ]);
    }

    public function template(Request $request, $tampilanId): JsonResponse
    {
        if (!$request->user()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated.'], 401);
        }

        $tampilan = Tampilan::with('isiTampilan.metadata')
            ->where('tampilan_id', $tampilanId)
            ->where('user_id', $request->user()->user_id)
            ->first();

        if (!$tampilan) {
            return response()->json(['status' => 'error', 'message' => 'Template tidak ditemukan.'], 404);
        }

        $metadataIds = $tampilan->isiTampilan->pluck('metadata_id');

        $data = Data::with(['metadata', 'location', 'time'])
            ->where('status', Data::STATUS_AVAILABLE)
            ->whereIn('metadata_id', $metadataIds)
            ->orderBy('date_inputed', 'desc')
            ->get();

        return response()->json([
            'status'   => 'success',
            'template' => [
                'id'            => $tampilan->tampilan_id,
                'nama_tampilan' => $tampilan->nama_tampilan,
                'metadata'      => $tampilan->isiTampilan->map(fn($i) => [
                    'metadata_id' => $i->metadata_id,
                    'nama'        => $i->metadata->nama ?? null,
                ]),
            ],
            'data' => $data,
        ]);
    }

    public function metadata(): JsonResponse
    {
        $list = \App\Models\Metadata::active()
            ->select('metadata_id','nama','alias','klasifikasi','tipe_data','satuan_data','frekuensi_penerbitan')
            ->orderBy('nama')
            ->get();

        return response()->json(['status' => 'success', 'data' => $list]);
    }

    private static function formatRow(Data $row, bool $detail = false): array
    {
        $base = [
            'id'             => $row->id,
            'metadata_id'    => $row->metadata_id,
            'metadata_nama'  => $row->metadata->nama ?? null,
            'satuan'         => $row->metadata->satuan_data ?? null,
            'location_id'    => $row->location_id,
            'lokasi'         => $row->location ? [
                'provinsi'  => $row->location->provinsi,
                'kabupaten' => $row->location->kabupaten,
                'kecamatan' => $row->location->kecamatan,
                'desa'      => $row->location->desa,
            ] : null,
            'time_id'        => $row->time_id,
            'waktu'          => $row->time ? [
                'year'    => $row->time->year,
                'quarter' => $row->time->quarter,
                'month'   => $row->time->month,
                'day'     => $row->time->day,
            ] : null,
            'nilai' => [
                'number'   => $row->number_value,
                'text'     => $row->text_value,
                'kategori' => $row->kategori_value,
                'other'    => $row->other,
            ],
            'date_inputed'   => $row->date_inputed,
        ];

        if ($detail) {
            $base['analisis_fenomena'] = $row->analisis_fenomena;
            $base['metadata_detail']   = $row->metadata ? [
                'konsep'               => $row->metadata->konsep,
                'definisi'             => $row->metadata->definisi,
                'rumus_perhitungan'    => $row->metadata->rumus_perhitungan,
                'frekuensi_penerbitan' => $row->metadata->frekuensi_penerbitan,
                'produsen_data'        => $row->metadata->produsen_data,
            ] : null;
        }

        return $base;
    }
}