<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class WaktuController extends Controller
{
    /**
     * Tampilkan halaman index dimensi waktu
     */
    public function index(Request $request)
    {
        $query = DB::table('time');

        if ($request->filled('search')) {
            $query->where('year', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('year')) {
            $query->where('year', $request->year);
        }

        $data = $query->paginate(20)
                      ->onEachSide(1)
                      ->withQueryString();

        $availableYears = DB::table('time')
            ->select('year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        return view('pages.dimensi_waktu.index', compact('data', 'availableYears'));
    }

    /**
     * Tampilkan form tambah dimensi waktu
     */
    public function create()
    {
        return view('pages.dimensi_waktu.create');
    }

    /**
     * Entry point store() — routing ke handler sesuai mode.
     *
     * Request payload untuk mode "full_year":
     *   mode  = full_year
     *   tahun = 2024
     *
     * Request payload untuk mode "custom":
     *   mode           = custom
     *   custom_decade  = 2020          (wajib minimal 1)
     *   custom_year    = 2024          (opsional)
     *   custom_quarter = 2             (opsional, wajib ada year jika diisi)
     *   custom_month   = 5             (opsional, wajib ada quarter jika diisi)
     *   custom_day     = 15            (opsional, wajib ada month jika diisi)
     */
    public function store(Request $request)
    {
        $request->validate([
            'mode' => ['required', 'in:full_year,custom'],
        ]);

        if ($request->mode === 'full_year') {
            return $this->storeFullYear($request);
        }

        return $this->storeCustom($request);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // MODE 1: Generate Full Year (behaviour lama, tidak berubah)
    // ─────────────────────────────────────────────────────────────────────────

    private function storeFullYear(Request $request)
    {
        $request->validate([
            'tahun' => ['required', 'integer', 'min:1900', 'max:2100'],
        ], [
            'tahun.required' => 'Tahun wajib diisi.',
            'tahun.integer'  => 'Tahun harus berupa angka.',
            'tahun.min'      => 'Tahun minimal 1900.',
            'tahun.max'      => 'Tahun maksimal 2100.',
        ]);

        $tahun = (int) $request->tahun;

        $exists = DB::table('time')
            ->where('year', $tahun)
            ->where('month', '!=', 0) // hindari collision dengan row custom
            ->exists();

        if ($exists) {
            return redirect()
                ->route('dimensi_waktu.create')
                ->withErrors(['tahun' => "Data full year untuk tahun {$tahun} sudah tersedia di database."])
                ->withInput();
        }

        $rows   = $this->generateDaysInYear($tahun);
        $chunks = array_chunk($rows, 500);

        foreach ($chunks as $chunk) {
            DB::table('time')->insert($chunk);
        }

        $totalDays = count($rows);

        return redirect()
            ->route('dimensi_waktu.index')
            ->with('success', "Berhasil menambahkan {$totalDays} data hari untuk tahun {$tahun}.");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // MODE 2: Custom Hierarki (mode baru — insert 1 row)
    // ─────────────────────────────────────────────────────────────────────────

    private function storeCustom(Request $request)
    {
        // ── Validasi field individual ──────────────────────────────────────
        $request->validate([
            'custom_decade'  => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'custom_year'    => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'custom_quarter' => ['nullable', 'integer', 'min:1', 'max:4'],
            'custom_month'   => ['nullable', 'integer', 'min:1', 'max:12'],
            'custom_day'     => ['nullable', 'integer', 'min:1', 'max:31'],
        ], [
            'custom_decade.integer'   => 'Dekade harus berupa angka.',
            'custom_decade.min'       => 'Dekade minimal 1900.',
            'custom_decade.max'       => 'Dekade maksimal 2100.',
            'custom_year.integer'     => 'Tahun harus berupa angka.',
            'custom_year.min'         => 'Tahun minimal 1900.',
            'custom_year.max'         => 'Tahun maksimal 2100.',
            'custom_quarter.integer'  => 'Kuartal harus berupa angka.',
            'custom_quarter.min'      => 'Kuartal minimal 1.',
            'custom_quarter.max'      => 'Kuartal maksimal 4.',
            'custom_month.integer'    => 'Bulan harus berupa angka.',
            'custom_month.min'        => 'Bulan minimal 1.',
            'custom_month.max'        => 'Bulan maksimal 12.',
            'custom_day.integer'      => 'Hari harus berupa angka.',
            'custom_day.min'          => 'Hari minimal 1.',
            'custom_day.max'          => 'Hari maksimal 31.',
        ]);

        // ── Ambil nilai dari request (null = tidak diisi) ──────────────────
        $decade  = $request->filled('custom_decade')  ? (int) $request->custom_decade  : null;
        $year    = $request->filled('custom_year')    ? (int) $request->custom_year    : null;
        $quarter = $request->filled('custom_quarter') ? (int) $request->custom_quarter : null;
        $month   = $request->filled('custom_month')   ? (int) $request->custom_month   : null;
        $day     = $request->filled('custom_day')     ? (int) $request->custom_day     : null;

        // ── Validasi: minimal decade harus diisi ──────────────────────────
        if ($decade === null) {
            return redirect()
                ->route('dimensi_waktu.create')
                ->withErrors(['custom_decade' => 'Dekade wajib diisi sebagai level teratas.'])
                ->withInput()
                ->with('mode', 'custom');
        }

        // ── Validasi hierarki (tidak boleh ada gap) ───────────────────────
        $hierarchyError = $this->validateHierarchy($decade, $year, $quarter, $month, $day);

        if ($hierarchyError) {
            return redirect()
                ->route('dimensi_waktu.create')
                ->withErrors([$hierarchyError['field'] => $hierarchyError['message']])
                ->withInput()
                ->with('mode', 'custom');
        }

        // ── Validasi konsistensi: year harus masuk dalam decade ───────────
        if ($year !== null) {
            $expectedDecade = (int) floor($year / 10) * 10;
            if ($decade !== $expectedDecade) {
                return redirect()
                    ->route('dimensi_waktu.create')
                    ->withErrors(['custom_year' => "Tahun {$year} tidak masuk dalam dekade {$decade}. Dekade yang benar: {$expectedDecade}."])
                    ->withInput()
                    ->with('mode', 'custom');
            }
        }

        // ── Validasi konsistensi: month harus masuk dalam quarter ─────────
        if ($quarter !== null && $month !== null) {
            $expectedQuarter = (int) ceil($month / 3);
            if ($quarter !== $expectedQuarter) {
                return redirect()
                    ->route('dimensi_waktu.create')
                    ->withErrors(['custom_month' => "Bulan {$month} tidak masuk dalam Q{$quarter}. Kuartal yang benar: Q{$expectedQuarter}."])
                    ->withInput()
                    ->with('mode', 'custom');
            }
        }

        // ── Bangun row: field yang tidak diisi → simpan sebagai 0 (ALL) ───
        $row = [
            'decade'  => $decade,
            'year'    => $year    ?? 0,
            'quarter' => $quarter ?? 0,
            'month'   => $month   ?? 0,
            'day'     => $day     ?? 0,
        ];

        // ── Cek duplikat: kombinasi persis sama sudah ada ─────────────────
        $exists = DB::table('time')
            ->where('decade',  $row['decade'])
            ->where('year',    $row['year'])
            ->where('quarter', $row['quarter'])
            ->where('month',   $row['month'])
            ->where('day',     $row['day'])
            ->exists();

        if ($exists) {
            return redirect()
                ->route('dimensi_waktu.create')
                ->withErrors(['custom_decade' => 'Data dengan kombinasi dimensi waktu yang sama sudah ada di database.'])
                ->withInput()
                ->with('mode', 'custom');
        }

        DB::table('time')->insert($row);

        $label = $this->buildRowLabel($row);

        return redirect()
            ->route('dimensi_waktu.index')
            ->with('success', "Berhasil menambahkan dimensi waktu: {$label}.");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Validasi hierarki: level bawah tidak boleh diisi jika level atasnya kosong.
     *
     * Aturan:
     *   year    butuh decade
     *   quarter butuh year
     *   month   butuh quarter
     *   day     butuh month
     *
     * @return array{field: string, message: string}|null  null = valid
     */
    private function validateHierarchy(
        ?int $decade,
        ?int $year,
        ?int $quarter,
        ?int $month,
        ?int $day
    ): ?array {
        if ($year !== null && $decade === null) {
            return [
                'field'   => 'custom_year',
                'message' => 'Tahun tidak bisa diisi tanpa mengisi Dekade terlebih dahulu.',
            ];
        }

        if ($quarter !== null && $year === null) {
            return [
                'field'   => 'custom_quarter',
                'message' => 'Kuartal tidak bisa diisi tanpa mengisi Tahun terlebih dahulu.',
            ];
        }

        if ($month !== null && $quarter === null) {
            return [
                'field'   => 'custom_month',
                'message' => 'Bulan tidak bisa diisi tanpa mengisi Kuartal terlebih dahulu.',
            ];
        }

        if ($day !== null && $month === null) {
            return [
                'field'   => 'custom_day',
                'message' => 'Hari tidak bisa diisi tanpa mengisi Bulan terlebih dahulu.',
            ];
        }

        return null;
    }

    /**
     * Buat label ringkas untuk flash message.
     * Contoh: "Dekade: 2020 | Year: 2024 | Quarter: 2 | Month: 0 (ALL) | Day: 0 (ALL)"
     */
    private function buildRowLabel(array $row): string
    {
        $parts = [];
        foreach ($row as $key => $val) {
            $parts[] = ucfirst($key) . ': ' . ($val === 0 ? '0 (ALL)' : $val);
        }
        return implode(' | ', $parts);
    }

    /**
     * Generate array of rows untuk semua hari dalam satu tahun.
     * (Tidak diubah dari versi asli)
     */
    private function generateDaysInYear(int $year): array
    {
        $rows    = [];
        $current = Carbon::create($year, 1, 1);
        $end     = Carbon::create($year, 12, 31);

        while ($current->lte($end)) {
            $rows[] = [
                'decade'  => (int) floor($current->year / 10) * 10,
                'year'    => $current->year,
                'quarter' => (int) ceil($current->month / 3),
                'month'   => $current->month,
                'day'     => $current->day,
            ];

            $current->addDay();
        }

        return $rows;
    }
}