<?php

namespace App\Http\Controllers;

use App\Models\Anomaly;
use App\Models\AnomalyReview;
use App\Models\AnomalyRule;
use App\Models\Data;
use App\Models\Metadata;
use App\Models\Location;
use App\Services\AnomalyDetectionService;
use App\Services\AuditTrailService;
use App\Services\WorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
// Use the container binding `dompdf.wrapper` instead of the facade import

class AnomalyControlController extends Controller
{
    public function __construct(
        private readonly AnomalyDetectionService $detector,
        private readonly WorkflowService         $workflow,
        private readonly AuditTrailService       $auditTrail,
    ) {}

    // ══════════════════════════════════════════════════════════
    // INDEX — Halaman Control Data Anomali
    // ══════════════════════════════════════════════════════════

    public function index(Request $request)
    {
        $query = Anomaly::with([
            'data.metadata',
            'data.location',
            'data.time',
            'data.user',
            'data.produsen',
            'data.satuan',
            'data.satuanAsal',
            'reviews.reviewer',
        ]);
    
        // ── Filter severity ───────────────────────────────────
        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }
    
        // ── Filter status ─────────────────────────────────────
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            $query->whereIn('status', [
                Anomaly::STATUS_WARNING,
                Anomaly::STATUS_UNDER_REVIEW,
            ]);
        }
    
        // ── Filter metadata ───────────────────────────────────
        if ($request->filled('metadata_id')) {
            $query->whereHas('data', fn($q) =>
                $q->where('metadata_id', $request->metadata_id)
            );
        }
    
        // ── Filter anomaly_type ───────────────────────────────
        if ($request->filled('anomaly_type')) {
            $query->where('anomaly_type', $request->anomaly_type);
        }
    
        // ── Search ─────────────────────────────────────────────
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('message', 'like', "%{$s}%")
                ->orWhereHas('data.metadata', fn($m) => $m->where('nama', 'like', "%{$s}%"))
                ->orWhereHas('data.location', fn($l) => $l->where('nama_wilayah', 'like', "%{$s}%"));
            });
        }
    
        // ── Sort ───────────────────────────────────────────────
        $anomalies = $query
            ->orderByRaw("FIELD(severity, 'critical','high','medium','low')")
            ->orderBy('detected_at', 'desc')
            ->paginate(20)
            ->withQueryString();
    
        // ── Enrich: tambah konteks per anomali ────────────────
        // Ini mengisi property sementara ke setiap anomali agar view bisa
        // menampilkan detail tanpa query tambahan per baris.
        $this->enrichAnomalyContext($anomalies->getCollection());
    
        // ── Data untuk filter dropdown ─────────────────────────
        $metadataList  = Metadata::where('status', 2)->orderBy('nama')->get(['metadata_id', 'nama']);
        $severityOpts  = Anomaly::severityOptions();
        $statusOpts    = Anomaly::statusOptions();
        $typeOpts      = [
            Anomaly::TYPE_EXTREME_INCREASE => 'Kenaikan Ekstrem',
            Anomaly::TYPE_EXTREME_DECREASE => 'Penurunan Ekstrem',
            Anomaly::TYPE_SOURCE_CONFLICT  => 'Konflik Sumber Data',
            Anomaly::TYPE_UNIT_CONFLICT    => 'Konflik Satuan',
            Anomaly::TYPE_UNREASONABLE     => 'Nilai Tidak Wajar',
        ];
    
        // ── Stats ─────────────────────────────────────────────
        $stats     = $this->workflow->getControlStats();
        $trendData = $this->workflow->getAnomalyTrend(7);
    
        $statusData = [
            'labels' => ['Warning', 'Under Review', 'Approved', 'Approved+Note', 'Rejected'],
            'values' => [
                Anomaly::where('status', Anomaly::STATUS_WARNING)->count(),
                Anomaly::where('status', Anomaly::STATUS_UNDER_REVIEW)->count(),
                Anomaly::where('status', Anomaly::STATUS_APPROVED)->count(),
                Anomaly::where('status', Anomaly::STATUS_APPROVED_WITH_NOTE)->count(),
                Anomaly::where('status', Anomaly::STATUS_REJECTED)->count(),
            ],
        ];
    
        return view('pages.anomaly.control.index', compact(
            'anomalies',
            'metadataList',
            'severityOpts',
            'statusOpts',
            'typeOpts',
            'stats',
            'trendData',
            'statusData',
        ));
    }

    // ── ENRICH ANOMALY CONTEXT ────────────────────────────────────────────────────
    //
    // Menempelkan property kontekstual ke setiap anomali.
    // Property yang ditambahkan (semua `public`, bukan relasi):
    //
    //   $anomaly->_ctx_type        string   'extreme_change' | 'source_conflict' | 'unreasonable' | 'other'
    //   $anomaly->_ctx_ref_label   string   Label nilai referensi  (mis. "Periode lalu", "Rata-rata")
    //   $anomaly->_ctx_ref_value   float|null Nilai referensi
    //   $anomaly->_ctx_curr_value  float|null Nilai saat ini
    //   $anomaly->_ctx_change_pct  float|null Persentase / z-score
    //   $anomaly->_ctx_sources     array    Hanya untuk source_conflict: list [{produsen, value, selisih, pct_diff, is_current}]
    //   $anomaly->_ctx_stats       array    Hanya untuk unreasonable: {mean, stddev, lower, upper, n, z_score}
    //
    // ─────────────────────────────────────────────────────────────────────────────
    
    private function enrichAnomalyContext(Collection $anomalies): void
    {
        // Kumpulkan semua kombinasi (metadata, location, time) untuk source_conflict
        // agar bisa batch-query sekaligus.
        $conflictKeys = $anomalies
            ->where('anomaly_type', Anomaly::TYPE_SOURCE_CONFLICT)
            ->map(fn($a) => [
                'metadata_id' => $a->data?->metadata_id,
                'location_id' => $a->data?->location_id,
                'time_id'     => $a->data?->time_id,
                'data_id'     => $a->data?->id,
            ])
            ->filter(fn($k) => $k['metadata_id'] !== null && $k['location_id'] !== null && $k['time_id'] !== null)
            ->unique(fn($k) => "{$k['metadata_id']}-{$k['location_id']}-{$k['time_id']}");
    
        // Batch load semua data sumber untuk source_conflict
        $conflictMap = [];
        foreach ($conflictKeys as $key) {
            $rows = Data::where('metadata_id', $key['metadata_id'])
                ->where('location_id', $key['location_id'])
                ->where('time_id', $key['time_id'])
                ->where('status', Data::STATUS_AVAILABLE)
                ->whereNotNull('number_value')
                ->with(['produsen', 'rujukan', 'satuan', 'satuanAsal'])
                ->get();

            $avg    = $rows->avg('number_value');
            $mapKey = "{$key['metadata_id']}-{$key['location_id']}-{$key['time_id']}";

            $distinctUnitIds = $rows->pluck('satuan_asal_id')->filter()->unique();
            $unitsConsistent = $distinctUnitIds->count() <= 1;

            $conflictMap[$mapKey] = $rows->map(fn($d) => [
                'data_id'          => $d->id,
                'produsen'         => $d->produsen?->nama_produsen ?? "Produsen #{$d->produsen_id}",
                'rujukan'          => $d->rujukan?->nama_rujukan ?? '—',
                'satuan'           => $d->satuanAsal?->nama_satuan ?? ($d->satuan?->nama_satuan ?? '—'),
                'units_consistent' => $unitsConsistent,
                'value'            => (float) $d->number_value,
                'selisih'          => round((float) $d->number_value - $avg, 4),
                'pct_diff'         => $avg > 0 ? round(abs(((float) $d->number_value - $avg) / $avg * 100), 2) : 0,
                'is_current'       => false,
            ])->toArray();
        }
    
        // Kumpulkan data historis untuk unreasonable (batch per metadata+location)
        $unreasonableKeys = $anomalies
            ->where('anomaly_type', Anomaly::TYPE_UNREASONABLE)
            ->map(fn($a) => [
                'metadata_id' => $a->data?->metadata_id,
                'location_id' => $a->data?->location_id,
                'data_id'     => $a->data?->id,
            ])
            ->filter(fn($k) => $k['metadata_id'] !== null && $k['location_id'] !== null && $k['data_id'] !== null);

        $statsMap = [];
        foreach ($unreasonableKeys as $key) {
            $mapKey = "{$key['metadata_id']}-{$key['location_id']}-{$key['data_id']}";
            if (isset($statsMap[$mapKey])) continue;

            // Gunakan ONLY STATUS_AVAILABLE agar konsisten dengan DataImport::detectOutliersViaDb
            $history = Data::where('metadata_id', $key['metadata_id'])
                ->where('location_id', $key['location_id'])
                ->where('id', '!=', $key['data_id'])
                ->where('status', Data::STATUS_AVAILABLE)
                ->whereNotNull('number_value')
                ->orderBy('time_id', 'desc')
                ->limit(20)
                ->pluck('number_value')
                ->map(fn($v) => (float) $v)
                ->toArray();

            // Gunakan AnomalyStatisticsService untuk perhitungan konsisten
            if (count($history) >= \App\Services\AnomalyStatisticsService::MIN_HISTORY_FOR_MEANINGFUL_STATS) {
                $stats = \App\Services\AnomalyStatisticsService::descriptiveStats($history);
                $statsMap[$mapKey] = [
                    'mean'   => $stats['mean'],
                    'stddev' => $stats['stddev'],
                    'lower'  => $stats['lower_3sigma'],
                    'upper'  => $stats['upper_3sigma'],
                    'n'      => $stats['n'],
                ];
            }
            // Jika < MIN_HISTORY, biarkan $statsMap[$mapKey] tidak di-set
            // → view akan fallback ke anomaly->previous_value & percentage_change
        }
    
        // Tempel property ke setiap anomali
        foreach ($anomalies as $anomaly) {
            $data = $anomaly->data;
    
            switch ($anomaly->anomaly_type) {
    
                // ── Kenaikan / Penurunan Ekstrem ──────────────────────────
                case Anomaly::TYPE_EXTREME_INCREASE:
                case Anomaly::TYPE_EXTREME_DECREASE:
                    $anomaly->_ctx_type       = 'extreme_change';
                    $anomaly->_ctx_ref_label  = 'Periode lalu';
                    $anomaly->_ctx_ref_value  = $anomaly->previous_value;
                    $anomaly->_ctx_curr_value = $anomaly->current_value;
                    $anomaly->_ctx_change_pct = $anomaly->percentage_change;
                    $anomaly->_ctx_sources    = [];
                    $anomaly->_ctx_stats      = [];
                break;
    
                // ── Konflik Sumber Data ───────────────────────────────────
                case Anomaly::TYPE_SOURCE_CONFLICT:
                    $mapKey = "{$data?->metadata_id}-{$data?->location_id}-{$data?->time_id}";
                    $sources = $conflictMap[$mapKey] ?? [];
    
                    // Tandai baris mana yang merupakan data saat ini
                    foreach ($sources as &$src) {
                        $src['is_current'] = ($src['data_id'] === $data?->id);
                    }
                    unset($src);

                    $avgRef = count($sources) > 0
                        ? round(array_sum(array_column($sources, 'value')) / count($sources), 4)
                        : null;
    
                    $anomaly->_ctx_type       = 'source_conflict';
                    $anomaly->_ctx_ref_label  = 'Rata-rata sumber';
                    $anomaly->_ctx_ref_value  = $avgRef;
                    $anomaly->_ctx_curr_value = $anomaly->current_value;
                    $anomaly->_ctx_change_pct = ($avgRef !== null && $avgRef > 0)
                        ? round(abs(((float) $anomaly->current_value - $avgRef) / $avgRef) * 100, 2)
                        : null;
                    $anomaly->_ctx_sources    = $sources;
                    $anomaly->_ctx_stats      = [];
                break;
    
                // ── Nilai Tidak Wajar ────────────────────────────────────
                case Anomaly::TYPE_UNREASONABLE:
                    $mapKey = "{$data?->metadata_id}-{$data?->location_id}-{$data?->id}";
                    $s      = $statsMap[$mapKey] ?? [];

                    // ── Hitung z-score ─────────────────────────────────────────
                    $zScore = null;
                    if (!empty($s) && isset($s['stddev']) && $s['stddev'] > 0) {
                        // Hitung ulang dari histori yang baru di-query
                        $zScore = round(
                            abs(((float) $anomaly->current_value - $s['mean']) / $s['stddev']),
                            4
                        );
                    } elseif ($anomaly->percentage_change !== null) {
                        // Fallback: pakai z-score yang sudah tersimpan saat deteksi
                        $zScore = (float) $anomaly->percentage_change;
                    }

                    // ── Ref value & stats ──────────────────────────────────────
                    if (!empty($s)) {
                        // Histori ditemukan dari DB → pakai hasil query
                        $refValue  = $s['mean'];
                        $refLabel  = "Rata-rata histori";
                        $statsData = $s;
                    } elseif ($anomaly->previous_value !== null) {
                        // Fallback: rekonstruksi stats dari nilai tersimpan di anomaly
                        $storedMean   = (float) $anomaly->previous_value;
                        $storedCurr   = (float) $anomaly->current_value;
                        $storedZ      = $zScore ?? 0;

                        // Estimasi stddev dari z-score dan mean yang tersimpan
                        // z = |curr - mean| / stddev  →  stddev = |curr - mean| / z
                        $absDiff      = abs($storedCurr - $storedMean);
                        $estStddev    = ($storedZ > 0) ? round($absDiff / $storedZ, 4) : 0;

                        $refValue  = $storedMean;
                        $refLabel  = "Rata-rata histori (saat deteksi)";
                        $statsData = [
                            'mean'   => $storedMean,
                            'stddev' => $estStddev,
                            'lower'  => round($storedMean - 3 * $estStddev, 4),
                            'upper'  => round($storedMean + 3 * $estStddev, 4),
                            'n'      => null,  // tidak diketahui dari rekonstruksi
                        ];
                    } else {
                        // FALLBACK TERAKHIR: Tidak ada statistik sama sekali
                        // Tapi tetap tampilkan marker bahwa data ini ditandai sebagai anomali
                        // Biasanya terjadi ketika user tandai outlier saat import tapi tidak ada histori
                        $refValue  = null;
                        $refLabel  = "Tandaan pengguna (data terbatas)";
                        $statsData = [
                            'mean'   => null,
                            'stddev' => 0,
                            'lower'  => null,
                            'upper'  => null,
                            'n'      => 0,  // Menandakan: tidak ada data untuk perhitungan
                        ];
                    }

                    $anomaly->_ctx_type       = 'unreasonable';
                    $anomaly->_ctx_ref_label  = $refLabel;
                    $anomaly->_ctx_ref_value  = $refValue;
                    $anomaly->_ctx_curr_value = $anomaly->current_value;
                    $anomaly->_ctx_change_pct = $zScore;
                    $anomaly->_ctx_sources    = [];
                    $anomaly->_ctx_stats      = $statsData;
                break;
                case Anomaly::TYPE_UNIT_CONFLICT:
                    $anomaly->_ctx_type       = 'unit_conflict';
                    $anomaly->_ctx_ref_label  = 'Satuan Rujukan';
                    $anomaly->_ctx_ref_value  = null;
                    $anomaly->_ctx_curr_value = $anomaly->current_value;
                    $anomaly->_ctx_change_pct = null;
                    $anomaly->_ctx_sources    = [];
                    $anomaly->_ctx_stats      = [
                        'satuan_metadata' => $data?->satuan?->nama_satuan ?? ($data?->metadata?->satuan_data ?? '-'),
                        'satuan_rujukan'  => $data?->satuanAsal?->nama_satuan ?? '-',
                    ];
                break;
    
                default:
                    $anomaly->_ctx_type       = 'other';
                    $anomaly->_ctx_ref_label  = '—';
                    $anomaly->_ctx_ref_value  = $anomaly->previous_value;
                    $anomaly->_ctx_curr_value = $anomaly->current_value;
                    $anomaly->_ctx_change_pct = $anomaly->percentage_change;
                    $anomaly->_ctx_sources    = [];
                    $anomaly->_ctx_stats      = [];
            }
        }
    }

    public function scanAll(Request $request)
    {
        try {
            $metaId   = $request->input('metadata_id');
            $scanType = $request->input('scan_type', 'all');

            $scanOptions = [
                'all'             => ['percentage_change', 'source_conflict', 'unreasonable_value'],
                'extreme'         => ['percentage_change'],
                'unreasonable'    => ['unreasonable_value'],
                'source_conflict' => ['source_conflict'],
            ];

            $enabledChecks = $scanOptions[$scanType] ?? $scanOptions['all'];
            $scanLabel = match ($scanType) {
                'extreme' => 'Kenaikan / Penurunan Ekstrem',
                'unreasonable' => 'Nilai Tidak Wajar',
                'source_conflict' => 'Konflik Sumber Data',
                default => 'Semua jenis anomali',
            };

            $stats = $this->detector->scanExistingData(
                100,    // batchSize
                true,   // scanAll
                $metaId, // metadata filter (nullable)
                $enabledChecks,
            );

            return response()->json([
                'success' => true,
                'message' => "Scan {$scanLabel} selesai. {$stats['scanned']} data diperiksa, {$stats['anomaliesFound']} anomali ditemukan.",
                'stats'   => $stats,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Scan gagal: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ══════════════════════════════════════════════════════════
    // SHOW — Detail satu anomali + histori review + perbandingan
    // ══════════════════════════════════════════════════════════

    public function show(Anomaly $anomaly)
    {
        $anomaly->load([
            'data.metadata.produsen',
            'data.location',
            'data.time',
            'data.user',
            'data.produsen',
            'data.rujukan.produsen',
            'reviews.reviewer',
            'data.satuan',
            'data.satuanAsal',
        ]);

        if (request()->wantsJson()) {
            return response()->json([
                'anomaly' => [
                    'message'           => $anomaly->message,
                    'severity_badge'    => $anomaly->severity,
                    'table_name'        => $anomaly->table_name,
                    'anomaly_type'      => ucwords(str_replace('_', ' ', $anomaly->anomaly_type)),
                    'previous_value'    => $anomaly->previous_value,
                    'current_value'     => $anomaly->current_value,
                    'percentage_change' => $anomaly->percentage_change,
                    'detected_at'       => \Carbon\Carbon::parse($anomaly->detected_at)->format('d M Y H:i'),
                ]
            ]);
        }

        $data = $anomaly->data;

        $conflictData = null;
        if ($anomaly->anomaly_type === Anomaly::TYPE_SOURCE_CONFLICT) {
            $conflictData = Data::where('metadata_id', $data->metadata_id)
                ->where('location_id', $data->location_id)
                ->where('time_id', $data->time_id)
                ->where('id', '!=', $data->id)
                ->where('status', Data::STATUS_AVAILABLE)
                ->whereNotNull('number_value')
                ->with(['metadata', 'location', 'time', 'user', 'produsen', 'rujukan', 'satuan', 'satuanAsal'])
                // Ambil yang selisihnya paling besar, sesuai logika checkSourceConflict()
                ->orderByRaw('ABS(number_value - ?) DESC', [(float) $data->number_value])
                ->first();
        }

        // ── Histori keputusan stakeholder ─────────────────────
        $decisionHistory = $this->workflow->getDecisionHistory($data->id);

        // ── Perbandingan antar sumber ─────────────────────────
        $sourceComparison = $this->detector->compareSourceValues(
            $data->metadata_id,
            $data->location_id,
            $data->time_id,
        );

        // ── Audit trail record ini ────────────────────────────
        $auditHistory = $this->auditTrail->getHistory('data', $data->id);

        // ── Data untuk form review ────────────────────────────
        $decisionOptions = AnomalyReview::decisionOptions();

        return view('pages.anomaly.control.show', compact(
            'anomaly',
            'data',
            'conflictData',
            'decisionHistory',
            'sourceComparison',
            'auditHistory',
            'decisionOptions',
        ));
    }

    // ══════════════════════════════════════════════════════════
    // REVIEW — Proses keputusan reviewer (POST)
    // ══════════════════════════════════════════════════════════

    public function review(Request $request, Anomaly $anomaly)
    {
        $request->validate([
            'decision'      => 'required|in:approved,approved_with_note,rejected,revised',
            'justification' => 'nullable|string|max:2000',
            'notes'         => 'nullable|string|max:1000',
        ]);

        try {
            $review = $this->workflow->reviewAnomaly(
                anomaly:       $anomaly,
                decision:      $request->decision,
                justification: $request->justification,
                notes:         $request->notes,
            );

            $label = $review->decision_label;

            if ($request->wantsJson()) {
                return response()->json([
                    'success'          => true,
                    'message'          => "Anomali #{$anomaly->anomalies_id} berhasil diproses: {$label}.",
                    'new_status'       => $anomaly->fresh()->status,
                    'new_status_label' => $anomaly->fresh()->status_label,
                    'new_status_style' => $anomaly->fresh()->status_style,
                    'workflow_status'  => $anomaly->data?->fresh()->workflow_status,
                ]);
            }

            return redirect()
                ->route('anomaly.control.index')
                ->with('success', "Anomali #{$anomaly->anomalies_id} berhasil diproses: {$label}.");

        } catch (ValidationException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'errors'  => $e->errors(),
                ], 422);
            }
            return back()->withErrors($e->errors())->withInput();

        } catch (\RuntimeException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 409);
            }
            return back()->withErrors(['decision' => $e->getMessage()]);
        }
    }

    // ══════════════════════════════════════════════════════════
    // BULK REVIEW — approve/reject banyak anomali (POST)
    // ══════════════════════════════════════════════════════════

    public function bulkReview(Request $request)
    {
        $request->validate([
            'anomaly_ids'   => 'required|array|min:1',
            'anomaly_ids.*' => 'integer|exists:anomalies,anomalies_id',
            'decision'      => 'required|in:approved,approved_with_note,rejected,revised',
            'justification' => 'nullable|string|max:2000',
        ]);

        // Jika decision butuh justification, wajib diisi
        if (AnomalyReview::requiresJustification($request->decision)
            && empty(trim($request->justification ?? ''))
        ) {
            return back()->withErrors([
                'justification' => "Justification wajib diisi untuk keputusan '{$request->decision}'.",
            ])->withInput();
        }

        $result = $this->workflow->bulkReview(
            anomalyIds:    $request->anomaly_ids,
            decision:      $request->decision,
            justification: $request->justification,
        );

        $msg = "{$result['success']} anomali berhasil diproses.";
        if ($result['skipped'] > 0) {
            $msg .= " {$result['skipped']} dilewati (sudah resolved).";
        }

        return redirect()->route('anomaly.control.index')->with('success', $msg);
    }

    // ══════════════════════════════════════════════════════════
    // SUBMIT FOR REVIEW — operator kirim ke Under Review (POST)
    // ══════════════════════════════════════════════════════════

    public function submitForReview(Request $request, Data $datum)
    {
        $request->validate([
            'note' => 'nullable|string|max:500',
        ]);

        try {
            $this->workflow->submitForReview($datum, $request->note);

            return redirect()
                ->route('anomaly.control.index')
                ->with('success', "Data #{$datum->id} berhasil dikirim untuk review.");

        } catch (\RuntimeException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    // ══════════════════════════════════════════════════════════
    // COMPARE SOURCES — AJAX perbandingan sumber data
    // ══════════════════════════════════════════════════════════

    public function compareSources(Request $request)
    {
        $request->validate([
            'metadata_id' => 'required|integer|exists:metadata,metadata_id',
            'location_id' => 'required|integer|exists:location,location_id',
            'time_id'     => 'required|integer|exists:time,time_id',
        ]);

        $comparison = $this->detector->compareSourceValues(
            $request->metadata_id,
            $request->location_id,
            $request->time_id,
        );

        return response()->json([
            'success' => true,
            'data'    => $comparison->values(),
        ]);
    }

    // ══════════════════════════════════════════════════════════
    // AUDIT TRAIL — AJAX histori perubahan satu data
    // ══════════════════════════════════════════════════════════

    public function auditHistory(Request $request, int $dataId)
    {
        $history = $this->auditTrail->getHistory('data', $dataId);

        return response()->json([
            'success' => true,
            'data'    => $history->map(fn($h) => [
                'audit_trail_id' => $h->audit_trail_id,
                'action_type'    => $h->action_type,
                'action_label'   => $h->action_label,
                'action_style'   => $h->action_style,
                'action_icon'    => $h->action_icon,
                'old_value'      => $h->old_value,
                'new_value'      => $h->new_value,
                'reason'         => $h->reason,
                'user_name'      => $h->user?->name ?? 'Sistem',
                'created_at'     => $h->created_at?->format('d/m/Y H:i'),
            ]),
        ]);
    }

    // ══════════════════════════════════════════════════════════
    // ANOMALY RULES — kelola threshold (index & update)
    // ══════════════════════════════════════════════════════════

    public function rules()
    {
        $rules = AnomalyRule::with('metadata')
            ->orderBy('metadata_id')
            ->orderBy('frekuensi')
            ->get();

        $metadataList = Metadata::where('status', 2)->orderBy('nama')->get(['metadata_id', 'nama']);

        return view('pages.anomaly.control.rules', compact('rules', 'metadataList'));
    }

    public function updateRule(Request $request, AnomalyRule $rule)
    {
        $request->validate([
            'threshold_low'      => 'required|numeric|min:0',
            'threshold_medium'   => 'required|numeric|gt:threshold_low',
            'threshold_high'     => 'required|numeric|gt:threshold_medium',
            'threshold_critical' => 'required|numeric|gt:threshold_high',
            'is_active'          => 'boolean',
        ]);

        $old = $rule->only([
            'threshold_low', 'threshold_medium', 'threshold_high', 'threshold_critical', 'is_active',
        ]);

        $rule->update([
            'threshold_low'      => $request->threshold_low,
            'threshold_medium'   => $request->threshold_medium,
            'threshold_high'     => $request->threshold_high,
            'threshold_critical' => $request->threshold_critical,
            'is_active'          => $request->boolean('is_active', true),
        ]);

        $this->auditTrail->recordUpdated(
            tableName: 'anomaly_rules',
            recordId:  $rule->anomaly_rules_id,
            oldValue:  $old,
            newValue:  $rule->fresh()->only([
                'threshold_low', 'threshold_medium', 'threshold_high', 'threshold_critical', 'is_active',
            ]),
            reason: 'Update threshold anomaly rule oleh admin',
        );

        return redirect()
            ->route('anomaly.control.rules')
            ->with('success', "Rule \"{$rule->name}\" berhasil diperbarui.");
    }

    // ══════════════════════════════════════════════════════════
    // STATS — AJAX data untuk grafik trend
    // ══════════════════════════════════════════════════════════

    public function trendStats(Request $request)
    {
        $days = (int) $request->input('days', 30);
        $days = min(max($days, 7), 90); // clamp: 7–90 hari

        $trend = $this->workflow->getAnomalyTrend($days);
        $stats = $this->workflow->getControlStats();

        return response()->json([
            'success' => true,
            'trend'   => $trend,
            'stats'   => $stats,
        ]);
    }

    public function exportReport(int $anomalyId)
    {
        $anomaly = Anomaly::findOrFail($anomalyId);
        $anomaly->load([
            'data.metadata.produsen',
            'data.location',
            'data.time',
            'data.user',
            'data.produsen',
            'data.rujukan.produsen',
            'data.satuan',
            'data.satuanAsal',
        ]);

        $data = $anomaly->data;

        $conflictData = null;
        if ($anomaly->anomaly_type === Anomaly::TYPE_SOURCE_CONFLICT) {
            $conflictData = Data::where('metadata_id', $data->metadata_id)
                ->where('location_id', $data->location_id)
                ->where('time_id', $data->time_id)
                ->where('id', '!=', $data->id)
                ->where('status', Data::STATUS_AVAILABLE)
                ->whereNotNull('number_value')
                ->with(['metadata', 'location', 'time', 'user', 'produsen', 'rujukan', 'satuan', 'satuanAsal'])
                ->orderByRaw('ABS(number_value - ?) DESC', [(float) $data->number_value])
                ->first();
        }

        $sourceComparison = $this->detector->compareSourceValues(
            $data->metadata_id,
            $data->location_id,
            $data->time_id,
        );

        if (!app()->bound('dompdf.wrapper')) {
            if (class_exists(\Barryvdh\DomPDF\ServiceProvider::class)) {
                app()->register(\Barryvdh\DomPDF\ServiceProvider::class);
            } else {
                throw new \RuntimeException('DOMPDF ServiceProvider not available; please install barryvdh/laravel-dompdf');
            }
        }

        $pdf = app('dompdf.wrapper')
            ->loadView('pages.anomaly.control.report_pdf', compact('anomaly', 'data', 'conflictData', 'sourceComparison'))
            ->setPaper('a4', 'portrait');

        return $pdf->download("Laporan-Anomali-{$anomaly->anomalies_id}.pdf");
    }
}