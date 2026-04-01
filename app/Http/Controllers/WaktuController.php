<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WaktuController extends Controller
{
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
            ->where('year', '!=', 0)
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        return view('pages.dimensi_waktu.index', compact('data', 'availableYears'));
    }

    public function create()
    {
        return view('pages.dimensi_waktu.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'mode' => ['required', 'in:full_year,custom'],
        ]);

        return $request->mode === 'full_year'
            ? $this->storeFullYear($request)
            : $this->storeCustom($request);
    }

    // =========================================================
    // MODE 1: Generate by Level
    //
    // stop_level values:
    //   decade   → 1 row:  (decade, 0, 0, 0, 0)
    //   year     → 1 row:  (decade, year, 0, 0, 0)
    //   semester → 2 rows: (decade, year, 1|2, 0, 0)
    //   quarter  → 4 rows: (decade, year, sem, 1–4, 0)
    //   month    → 12 rows:(decade, year, sem, q, 1–12)
    // =========================================================
    private function storeFullYear(Request $request)
    {
        $request->validate([
            'tahun'      => ['required', 'integer', 'min:1900', 'max:2100'],
            'stop_level' => ['required', 'in:decade,year,semester,quarter,month'],
        ]);

        $tahun     = (int) $request->tahun;
        $stopLevel = $request->stop_level;
        $decade    = (int) (floor($tahun / 10) * 10);

        $rows = $this->generateRowsByLevel($tahun, $decade, $stopLevel);

        if (empty($rows)) {
            return back()
                ->withErrors(['tahun' => 'Tidak ada data yang dapat digenerate.'])
                ->withInput();
        }

        // Duplicate check
        $conflicts = 0;
        foreach ($rows as $row) {
            if (DB::table('time')->where($row)->exists()) {
                $conflicts++;
            }
        }

        if ($conflicts > 0) {
            return back()
                ->withErrors(['tahun' => "Sebagian atau seluruh data sudah ada ({$conflicts} baris duplikat)."])
                ->withInput();
        }

        DB::transaction(function () use ($rows) {
            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('time')->insert($chunk);
            }
        });

        $total = count($rows);
        return redirect()
            ->route('dimensi_waktu.index')
            ->with('success', "Berhasil generate {$total} baris data (level: {$stopLevel}).");
    }

    // =========================================================
    // MODE 2: Custom (single row, manual input)
    // =========================================================
    private function storeCustom(Request $request)
    {
        $request->validate([
            'custom_decade'   => ['required', 'integer', 'min:1900', 'max:2100'],
            'custom_year'     => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'custom_semester' => ['nullable', 'integer', 'min:0', 'max:2'],
            'custom_quarter'  => ['nullable', 'integer', 'min:0', 'max:4'],
            'custom_month'    => ['nullable', 'integer', 'min:0', 'max:12'],
        ]);

        $decade   = (int) $request->custom_decade;
        $year     = $request->filled('custom_year')     ? (int) $request->custom_year     : 0;
        $semester = $request->filled('custom_semester') ? (int) $request->custom_semester : 0;
        $quarter  = $request->filled('custom_quarter')  ? (int) $request->custom_quarter  : 0;
        $month    = $request->filled('custom_month')    ? (int) $request->custom_month    : 0;

        // Year must belong to the chosen decade
        if ($year !== 0) {
            $expectedDecade = (int) (floor($year / 10) * 10);
            if ($decade !== $expectedDecade) {
                return back()
                    ->withErrors(['custom_year' => "Tahun {$year} tidak sesuai dengan dekade {$decade}."])
                    ->withInput();
            }
        }

        // Cannot fill lower levels if parent is empty
        if ($semester !== 0 && $year === 0) {
            return back()
                ->withErrors(['custom_semester' => 'Isi tahun terlebih dahulu sebelum memilih semester.'])
                ->withInput();
        }
        if ($quarter !== 0 && $year === 0) {
            return back()
                ->withErrors(['custom_quarter' => 'Isi tahun terlebih dahulu sebelum memilih kuartal.'])
                ->withInput();
        }
        if ($month !== 0 && $quarter === 0) {
            return back()
                ->withErrors(['custom_month' => 'Isi kuartal terlebih dahulu sebelum memilih bulan.'])
                ->withInput();
        }

        // Semester ↔ Quarter consistency
        if ($semester !== 0 && $quarter !== 0) {
            $expectedSem = $quarter <= 2 ? 1 : 2;
            if ($semester !== $expectedSem) {
                return back()
                    ->withErrors(['custom_quarter' => "Kuartal {$quarter} tidak sesuai dengan Semester {$semester}."])
                    ->withInput();
            }
        }

        // Quarter ↔ Month consistency
        if ($quarter !== 0 && $month !== 0) {
            $expectedQ = (int) ceil($month / 3);
            if ($quarter !== $expectedQ) {
                return back()
                    ->withErrors(['custom_month' => "Bulan {$month} tidak sesuai dengan Kuartal {$quarter}."])
                    ->withInput();
            }
        }

        // Semester ↔ Month consistency
        if ($semester !== 0 && $month !== 0) {
            $expectedSem = $month <= 6 ? 1 : 2;
            if ($semester !== $expectedSem) {
                return back()
                    ->withErrors(['custom_month' => "Bulan {$month} tidak sesuai dengan Semester {$semester}."])
                    ->withInput();
            }
        }

        $row = compact('decade', 'year', 'semester', 'quarter', 'month');

        if (DB::table('time')->where($row)->exists()) {
            return back()
                ->withErrors(['custom_decade' => 'Data dengan kombinasi ini sudah ada.'])
                ->withInput();
        }

        DB::table('time')->insert($row);

        return redirect()
            ->route('dimensi_waktu.index')
            ->with('success', 'Berhasil menambahkan 1 baris data.');
    }

    // =========================================================
    // HELPERS
    // =========================================================

    private function generateRowsByLevel(int $year, int $decade, string $stopLevel): array
    {
        $rows = [];

        switch ($stopLevel) {
            case 'decade':
                $rows[] = ['decade' => $decade, 'year' => 0, 'semester' => 0, 'quarter' => 0, 'month' => 0];
                break;

            case 'year':
                $rows[] = ['decade' => $decade, 'year' => $year, 'semester' => 0, 'quarter' => 0, 'month' => 0];
                break;

            case 'semester':
                foreach ([1, 2] as $sem) {
                    $rows[] = ['decade' => $decade, 'year' => $year, 'semester' => $sem, 'quarter' => 0, 'month' => 0];
                }
                break;

            case 'quarter':
                foreach ([1, 2, 3, 4] as $q) {
                    $sem    = $q <= 2 ? 1 : 2;
                    $rows[] = ['decade' => $decade, 'year' => $year, 'semester' => $sem, 'quarter' => $q, 'month' => 0];
                }
                break;

            case 'month':
                for ($m = 1; $m <= 12; $m++) {
                    $sem    = $m <= 6 ? 1 : 2;
                    $q      = (int) ceil($m / 3);
                    $rows[] = ['decade' => $decade, 'year' => $year, 'semester' => $sem, 'quarter' => $q, 'month' => $m];
                }
                break;
        }

        return $rows;
    }
}