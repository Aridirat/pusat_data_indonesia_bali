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

        // Search by year
        if ($request->filled('search')) {
            $query->where('year', 'like', '%' . $request->search . '%');
        }

        // Filter by year (opsional dropdown)
        if ($request->filled('year')) {
            $query->where('year', $request->year);
        }

        $data = $query->paginate(20)
                    ->onEachSide(1)
                    ->withQueryString();

        // Ambil list tahun yang sudah ada untuk filter
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
     * Generate dan simpan semua hari dalam satu tahun ke tabel time
     */
    public function store(Request $request)
    {
        $request->validate([
            'tahun' => [
                'required',
                'integer',
                'min:1900',
                'max:2100',
            ],
        ], [
            'tahun.required' => 'Tahun wajib diisi.',
            'tahun.integer'  => 'Tahun harus berupa angka.',
            'tahun.min'      => 'Tahun minimal 1900.',
            'tahun.max'      => 'Tahun maksimal 2100.',
        ]);

        $tahun = (int) $request->tahun;

        // Cek apakah tahun sudah pernah di-generate
        $exists = DB::table('time')->where('year', $tahun)->exists();

        if ($exists) {
            return redirect()
                ->route('dimensi_waktu.create')
                ->withErrors(['tahun' => "Data tahun {$tahun} sudah tersedia di database."])
                ->withInput();
        }

        // Generate semua hari dalam tahun tersebut
        $rows = $this->generateDaysInYear($tahun);

        // Insert batch untuk efisiensi
        $chunks = array_chunk($rows, 500);
        foreach ($chunks as $chunk) {
            DB::table('time')->insert($chunk);
        }

        $totalDays = count($rows);

        return redirect()
            ->route('dimensi_waktu.index')
            ->with('success', "Berhasil menambahkan {$totalDays} data hari untuk tahun {$tahun}.");
    }

    /**
     * Generate array of rows untuk semua hari dalam satu tahun
     *
     * @param int $year
     * @return array
     */
    private function generateDaysInYear(int $year): array
    {
        $rows   = [];
        $start  = Carbon::create($year, 1, 1);
        $end    = Carbon::create($year, 12, 31);

        $current = $start->copy();

        while ($current->lte($end)) {
            $rows[] = [
                'decade'  => (int) floor($current->year / 10) * 10,  // e.g. 2020 untuk tahun 2023
                'year'    => $current->year,
                'quarter' => (int) ceil($current->month / 3),         // 1-4
                'month'   => $current->month,
                'day'     => $current->day,
            ];

            $current->addDay();
        }

        return $rows;
    }
}