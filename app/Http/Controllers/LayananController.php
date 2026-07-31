<?php

namespace App\Http\Controllers;

use App\Models\Layanan;
use App\Models\LayananFitur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\FiturKatalog;

class LayananController extends Controller
{
    // ── Index ──────────────────────────────────────────────────
    public function index(Request $request)
    {
        $query = Layanan::withCount('fiturs');

        if ($request->filled('search')) {
            $query->where('nama_layanan', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $layanan = $query
            ->orderBy('urutan')
            ->orderBy('layanan_id')
            ->paginate(10)
            ->withQueryString();

        return view('pages.layanan.index', compact('layanan'));
    }

    // ── Create ─────────────────────────────────────────────────
    public function create()
    {
        $fiturKatalog = FiturKatalog::orderBy('nama_fitur')->pluck('nama_fitur');

        return view('pages.layanan.create', compact('fiturKatalog'));
    }

    // ── Store ──────────────────────────────────────────────────
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_layanan' => 'required|string|max:200',
            'harga'        => 'required|numeric|min:0',
            'durasi'       => 'required|integer|min:1',
            'durasi_type'  => 'required|in:harian,mingguan,bulanan,tahunan,selamanya',
            'status'       => 'required|in:publish,pending,takedown',
            'is_popular'   => 'nullable|boolean',
            'urutan'       => 'nullable|integer|min:0',
            'thumbnail'    => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'fiturs'       => 'nullable|array',
            'fiturs.*'     => 'nullable|string|max:200',
            'category'     => 'nullable|in:personal,organisasi',
            'max_seats'    => 'nullable|integer|min:1',
            'max_concurrent_sessions' => 'nullable|integer|min:1',
            'max_templates'=> 'nullable|integer|min:0',
        ], $this->messages());

        if ($request->hasFile('thumbnail')) {
            $validated['thumbnail'] = $request
                ->file('thumbnail')
                ->store('layanan', 'public');
        }

        $layanan = Layanan::create(array_merge($validated, $this->prepareLayananData($request)));

        $this->syncFiturs($layanan, $request->input('fiturs', []));

        return redirect()
            ->route('admin.layanan.index')
            ->with('success', 'Layanan berhasil ditambahkan.');
    }

    // ── Show ───────────────────────────────────────────────────
    public function show(Layanan $layanan)
    {
        $layanan->load('fiturs');

        return view('pages.layanan.show', compact('layanan'));
    }

    // ── Edit ───────────────────────────────────────────────────
    public function edit(Layanan $layanan)
    {
        $layanan->load('fiturs');
        $fiturKatalog = FiturKatalog::orderBy('nama_fitur')->pluck('nama_fitur');

        return view('pages.layanan.edit', compact('layanan', 'fiturKatalog'));
    }

    // ── Update ─────────────────────────────────────────────────
    public function update(Request $request, Layanan $layanan)
    {
        if ($request->durasi_type === 'selamanya') {
            $request->merge([
                'durasi' => 1
            ]);
        }

        $validated = $request->validate([
            'nama_layanan' => 'required|string|max:200',
            'harga'        => 'required|numeric|min:0',
            'durasi'       => 'required|integer|min:1',
            'durasi_type'  => 'required|in:harian,mingguan,bulanan,tahunan,selamanya',
            'status'       => 'required|in:publish,pending,takedown',
            'is_popular'   => 'nullable|boolean',
            'urutan'       => 'nullable|integer|min:0',
            'thumbnail'    => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'fiturs'       => 'nullable|array',
            'fiturs.*'     => 'nullable|string|max:200',
            'category'     => 'nullable|in:personal,organisasi',
            'max_seats'    => 'nullable|integer|min:1',
            'max_concurrent_sessions' => 'nullable|integer|min:1',
            'max_templates'=> 'nullable|integer|min:0',
        ], $this->messages());

        if ($request->hasFile('thumbnail')) {
            if ($layanan->thumbnail) {
                Storage::disk('public')->delete($layanan->thumbnail);
            }
            $validated['thumbnail'] = $request
                ->file('thumbnail')
                ->store('layanan', 'public');
        } else {
            unset($validated['thumbnail']);
        }

        $layanan->update(array_merge($validated, $this->prepareLayananData($request)));

        $this->syncFiturs($layanan, $request->input('fiturs', []));

        return redirect()
            ->route('admin.layanan.index')
            ->with('success', 'Layanan berhasil diperbarui.');
    }

    // ── Destroy ────────────────────────────────────────────────
    public function destroy(Layanan $layanan)
    {
        if ($layanan->thumbnail) {
            Storage::disk('public')->delete($layanan->thumbnail);
        }

        $layanan->delete();

        return redirect()
            ->route('admin.layanan.index')
            ->with('success', 'Layanan berhasil dihapus.');
    }

    // ── Status Actions ─────────────────────────────────────────
    public function publish(Layanan $layanan)
    {
        $layanan->update(['status' => 'publish']);
        return back()->with('success', 'Layanan berhasil dipublish.');
    }

    public function takedown(Layanan $layanan)
    {
        $layanan->update(['status' => 'takedown']);
        return back()->with('success', 'Layanan berhasil di-takedown.');
    }

    public function draft(Layanan $layanan)
    {
        $layanan->update(['status' => 'pending']);
        return back()->with('success', 'Layanan berhasil dijadikan draft.');
    }

    // ── Toggle Popular ─────────────────────────────────────────
    public function togglePopular(Layanan $layanan)
    {
        $layanan->update(['is_popular' => ! $layanan->is_popular]);

        $label = $layanan->is_popular ? 'ditandai populer' : 'dihapus dari populer';

        return back()->with('success', "Layanan berhasil {$label}.");
    }

    // ── Private Helpers ────────────────────────────────────────
    private function syncFiturs(Layanan $layanan, array $fiturs): void
    {
        $layanan->fiturs()->delete();

        $rows = [];

        foreach (
            array_filter($fiturs, fn($f) => trim((string) $f) !== '')
            as $index => $nama
        ) {
            $rows[] = [
                'layanan_id' => $layanan->layanan_id,
                'nama_fitur' => trim($nama),
                'aktif'      => true,
                'urutan'     => $index,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            FiturKatalog::firstOrCreate(['nama_fitur' => $nama]);
        }

        if (! empty($rows)) {
            LayananFitur::insert($rows);
        }
    }

    private function prepareLayananData(Request $request): array
    {
        $audienceType = $request->input('audience_type', 'personal');
        $category = $request->input('category', $audienceType === 'organization' ? 'organisasi' : 'personal');

        $data = [
            'audience_type' => $audienceType,
            'category' => $category,
            'max_seats' => $request->input('max_seats') ?: null,
            'max_concurrent_sessions' => $request->filled('max_concurrent_sessions')
                ? (int) $request->input('max_concurrent_sessions')
                : 1,
            'max_templates' => $request->input('max_templates'),
        ];

        if ($audienceType === 'personal') {
            $data['max_seats'] = null;
            $data['max_concurrent_sessions'] = 1;
            $data['max_templates'] = $data['max_templates'] ?: 10;
        }

        return $data;
    }

    private function messages(): array
    {
        return [
            'nama_layanan.required' => 'Nama layanan wajib diisi.',
            'harga.required'        => 'Harga wajib diisi.',
            'harga.numeric'         => 'Harga harus berupa angka.',
            'durasi.required'       => 'Durasi wajib diisi.',
            'durasi_type.required'  => 'Tipe durasi wajib dipilih.',
            'status.required'       => 'Status wajib dipilih.',
            'thumbnail.image'       => 'Thumbnail harus berupa gambar.',
            'thumbnail.mimes'       => 'Format thumbnail: jpg, jpeg, png, webp.',
            'thumbnail.max'         => 'Ukuran thumbnail maksimal 2MB.',
        ];
    }
}