@extends('layouts.main')

@section('content')

<div class="mt-2 bg-white rounded-xl shadow p-6">

    {{-- ══════════════════════════════════════════════
         HEADER
    ══════════════════════════════════════════════ --}}
    <div class="flex justify-between items-start flex-wrap gap-3">
        <div>
            <h1 class="text-xl font-bold text-gray-800">Daftar Metadata</h1>
            <p class="text-sm text-gray-400 mt-1">Metadata aktif yang tersedia dalam sistem</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">

            {{-- Badge Pending --}}
            @if($pendingCount > 0)
                <a href="{{ route('metadata.approval') }}"
                   class="flex items-center gap-2 px-3 py-1.5 rounded-md text-xs font-semibold
                          border transition-colors"
                   style="background:#fffbeb; border-color:#fde68a; color:#b45309;"
                   onmouseover="this.style.background='#fef3c7'"
                   onmouseout="this.style.background='#fffbeb'">
                    <i class="fas fa-clock"></i>
                    {{ $pendingCount }} Pending Approval
                </a>
            @endif

            {{-- Tombol Export --}}
            <button onclick="openExportModal()"
                    class="flex items-center gap-2 px-3 py-1.5 rounded-md text-xs font-semibold
                           text-white transition-colors shadow-sm"
                    style="background:#059669;"
                    onmouseover="this.style.background='#047857'"
                    onmouseout="this.style.background='#059669'">
                <i class="fas fa-file-excel"></i>
                Export Excel
            </button>

            {{-- Tombol Tambah --}}
            <a href="{{ route('metadata.create') }}"
               class="flex items-center gap-2 px-3 py-1.5 rounded-md text-xs font-semibold
                      text-white transition-colors shadow-sm"
               style="background:#0284c7;"
               onmouseover="this.style.background='#0369a1'"
               onmouseout="this.style.background='#0284c7'">
                <i class="fas fa-plus"></i>
                Tambah Metadata
            </a>
        </div>
    </div>

    {{-- Alert --}}
    @if(session('success'))
        <div class="mt-4 flex items-center gap-3 bg-green-50 border border-green-200 text-green-700
                    px-4 py-3 rounded-lg text-sm">
            <i class="fas fa-check-circle text-green-500 shrink-0"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════
         SEARCH + RESET
    ══════════════════════════════════════════════ --}}
    <div class="flex items-center gap-2 mt-5">
        <form method="GET" class="flex items-center gap-2 flex-1">
            {{-- Hidden inputs: pertahankan filter kolom saat search --}}
            <input type="hidden" name="filter_nama"        value="{{ request('filter_nama') }}">
            <input type="hidden" name="filter_klasifikasi" value="{{ request('filter_klasifikasi') }}">
            <input type="hidden" name="filter_tipe_data"   value="{{ request('filter_tipe_data') }}">
            <input type="hidden" name="filter_satuan"      value="{{ request('filter_satuan') }}">
            <input type="hidden" name="filter_frekuensi"   value="{{ request('filter_frekuensi') }}">
            <input type="hidden" name="filter_produsen_id" value="{{ request('filter_produsen_id') }}">

            <div class="relative flex-1 max-w-xs">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Cari nama, alias, atau tag..."
                       class="w-full border border-gray-300 rounded-md pl-8 pr-3 py-1.5 text-sm
                              focus:outline-none focus:ring-2 focus:ring-sky-400">
                <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
            </div>
            <button type="submit"
                    class="px-3 py-1.5 text-xs font-semibold text-white rounded-md transition-colors"
                    style="background:#0284c7;"
                    onmouseover="this.style.background='#0369a1'"
                    onmouseout="this.style.background='#0284c7'">
                Cari
            </button>
        </form>

        {{-- Reset semua filter --}}
        @if(request()->hasAny(['search','filter_nama','filter_klasifikasi','filter_tipe_data','filter_satuan','filter_frekuensi','filter_produsen_id']))
            <a href="{{ route('metadata.index') }}"
               class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-md
                      border border-red-200 text-red-400 hover:bg-red-50 transition-colors"
               title="Reset semua filter">
                <i class="fas fa-times-circle"></i> Reset Filter
            </a>
        @endif

        {{-- Info jumlah data --}}
        <p class="text-xs text-gray-400 ml-auto">
            {{ number_format($data->total()) }} metadata aktif
        </p>
    </div>

    {{-- ══════════════════════════════════════════════
         TABLE
    ══════════════════════════════════════════════ --}}
    <div class="mt-4 border rounded-lg overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider border-b">

                {{-- Baris 1: Judul kolom --}}
                <tr>
                    <th class="px-4 py-3 font-semibold w-10">No</th>
                    <th class="px-4 py-3 font-semibold min-w-52">Nama</th>
                    <th class="px-4 py-3 font-semibold min-w-28">Klasifikasi</th>
                    <th class="px-4 py-3 font-semibold min-w-20">Tipe Data</th>
                    <th class="px-4 py-3 font-semibold min-w-20">Satuan</th>
                    <th class="px-4 py-3 font-semibold min-w-24">Frekuensi</th>
                    <th class="px-4 py-3 font-semibold min-w-36">Produsen</th>
                    <th class="px-4 py-3 font-semibold text-center min-w-24">Aksi</th>
                </tr>

                {{-- Baris 2: Filter per kolom (server-side) --}}
                <tr class="bg-white border-t border-gray-100">
                    <td class="px-2 py-1.5"></td>

                    {{-- Filter Nama --}}
                    <td class="px-2 py-1.5">
                        <input type="text" id="filterNama"
                               value="{{ request('filter_nama') }}"
                               placeholder="Filter nama..."
                               class="w-full border border-gray-200 rounded px-2 py-1 text-xs
                                      focus:outline-none focus:ring-1 focus:ring-sky-300"
                               onkeydown="if(event.key==='Enter') applyFilters()">
                    </td>

                    {{-- Filter Klasifikasi --}}
                    <td class="px-2 py-1.5">
                        <select id="filterKlasifikasi"
                                class="w-full border border-gray-200 rounded px-2 py-1 text-xs
                                       focus:outline-none focus:ring-1 focus:ring-sky-300"
                                onchange="applyFilters()">
                            <option value="">Semua</option>
                            @foreach($klasifikasiList as $k)
                                <option value="{{ $k }}"
                                    {{ request('filter_klasifikasi') === $k ? 'selected' : '' }}>
                                    {{ $k }}
                                </option>
                            @endforeach
                        </select>
                    </td>

                    {{-- Filter Tipe Data --}}
                    <td class="px-2 py-1.5">
                        <select id="filterTipeData"
                                class="w-full border border-gray-200 rounded px-2 py-1 text-xs
                                       focus:outline-none focus:ring-1 focus:ring-sky-300"
                                onchange="applyFilters()">
                            <option value="">Semua</option>
                            @foreach($tipeDataList as $t)
                                <option value="{{ $t }}"
                                    {{ request('filter_tipe_data') === $t ? 'selected' : '' }}>
                                    {{ $t }}
                                </option>
                            @endforeach
                        </select>
                    </td>

                    {{-- Filter Satuan --}}
                    <td class="px-2 py-1.5">
                        <input type="text" id="filterSatuan"
                               value="{{ request('filter_satuan') }}"
                               placeholder="Filter satuan..."
                               class="w-full border border-gray-200 rounded px-2 py-1 text-xs
                                      focus:outline-none focus:ring-1 focus:ring-sky-300"
                               onkeydown="if(event.key==='Enter') applyFilters()">
                    </td>

                    {{-- Filter Frekuensi --}}
                    <td class="px-2 py-1.5">
                        <select id="filterFrekuensi"
                                class="w-full border border-gray-200 rounded px-2 py-1 text-xs
                                       focus:outline-none focus:ring-1 focus:ring-sky-300"
                                onchange="applyFilters()">
                            <option value="">Semua</option>
                            @foreach($frekuensiList as $f)
                                <option value="{{ $f }}"
                                    {{ request('filter_frekuensi') === $f ? 'selected' : '' }}>
                                    {{ $f }}
                                </option>
                            @endforeach
                        </select>
                    </td>

                    {{-- Filter Produsen --}}
                    <td class="px-2 py-1.5">
                        <select id="filterProdusen"
                                class="w-full border border-gray-200 rounded px-2 py-1 text-xs
                                       focus:outline-none focus:ring-1 focus:ring-sky-300"
                                onchange="applyFilters()">
                            <option value="">Semua</option>
                            @foreach($produsenList as $p)
                                <option value="{{ $p->produsen_id }}"
                                    {{ (string)request('filter_produsen_id') === (string)$p->produsen_id ? 'selected' : '' }}>
                                    {{ $p->nama_produsen }}
                                </option>
                            @endforeach
                        </select>
                    </td>

                    {{-- Reset filter kolom --}}
                    <td class="px-2 py-1.5 text-center">
                        <button onclick="resetFilters()"
                                class="text-xs text-gray-400 hover:text-red-400 transition-colors whitespace-nowrap"
                                title="Reset filter kolom">
                            <i class="fas fa-filter-slash"></i> Reset
                        </button>
                    </td>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-100">
                @forelse($data as $index => $item)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 text-gray-400 text-xs">
                            {{ $data->firstItem() + $index }}
                        </td>

                        <td class="px-4 py-3">
                            <p class="font-semibold text-gray-800 leading-snug">{{ $item->nama }}</p>
                            @if($item->alias)
                                <p class="text-xs text-gray-400 italic mt-0.5">{{ $item->alias }}</p>
                            @endif
                            {{-- Tags --}}
                            @if($item->tag && $item->tag !== '-')
                                <div class="flex flex-wrap gap-1 mt-1.5">
                                    @foreach(array_slice(explode(',', $item->tag), 0, 3) as $tag)
                                        <span class="text-xs px-1.5 py-0.5 rounded-full font-medium"
                                              style="background:#e0f2fe; color:#0369a1;">
                                            #{{ trim($tag) }}
                                        </span>
                                    @endforeach
                                    @if(count(explode(',', $item->tag)) > 3)
                                        <span class="text-xs text-gray-400">+{{ count(explode(',', $item->tag)) - 3 }}</span>
                                    @endif
                                </div>
                            @endif
                        </td>

                        <td class="px-4 py-3">
                            <span class="px-2 py-1 bg-sky-50 text-sky-700 text-xs rounded-full font-medium">
                                {{ $item->klasifikasi }}
                            </span>
                        </td>

                        <td class="px-4 py-3 text-gray-600 text-xs">{{ $item->tipe_data }}</td>

                        <td class="px-4 py-3 text-gray-600 text-xs">{{ $item->satuan_data }}</td>

                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium"
                                  style="background:#fffbeb; color:#b45309;">
                                {{ $item->frekuensi_penerbitan }}
                            </span>
                        </td>

                        <td class="px-4 py-3 text-gray-600 text-xs">
                            {{ $item->produsen?->nama_produsen
                                ? Str::limit($item->produsen->nama_produsen, 28)
                                : '-' }}
                        </td>

                        <td class="px-4 py-3 text-center">
                            <a href="{{ route('metadata.detail', $item->metadata_id) }}"
                               class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold
                                      text-white rounded-md transition-colors shadow-sm"
                               style="background:#0284c7;"
                               onmouseover="this.style.background='#0369a1'"
                               onmouseout="this.style.background='#0284c7'">
                                <i class="fas fa-eye"></i>
                                Detail
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-16 text-center">
                            <div class="flex flex-col items-center gap-3 text-gray-400">
                                <i class="fas fa-database text-4xl text-gray-200"></i>
                                <p class="font-medium text-gray-500">Tidak ada metadata ditemukan</p>
                                @if(request()->hasAny(['search','filter_nama','filter_klasifikasi','filter_tipe_data','filter_satuan','filter_frekuensi','filter_produsen_id']))
                                    <a href="{{ route('metadata.index') }}"
                                       class="text-xs text-sky-500 hover:text-sky-700 underline">
                                        Hapus semua filter
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- PAGINATION --}}
    @if($data->hasPages())
        <div class="mt-5 flex flex-col sm:flex-row items-center justify-between gap-3 text-sm text-gray-500">
            <p>
                Menampilkan {{ $data->firstItem() }}–{{ $data->lastItem() }}
                dari {{ number_format($data->total()) }} data
            </p>
            {{ $data->withQueryString()->links() }}
        </div>
    @endif

</div>

{{-- ══════════════════════════════════════════════════════════════
     MODAL EXPORT EXCEL
══════════════════════════════════════════════════════════════ --}}
<div id="exportModal"
     class="fixed inset-0 z-50 hidden items-center justify-center"
     style="background: rgba(0,0,0,0.45);">

    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">

        {{-- Header modal --}}
        <div class="flex items-center justify-between px-6 py-4 border-b"
             style="background: linear-gradient(90deg,#0284c7,#38bdf8);">
            <div class="flex items-center gap-2 text-white">
                <i class="fas fa-file-excel text-lg"></i>
                <h2 class="text-base font-bold">Export Metadata ke Excel</h2>
            </div>
            <button onclick="closeExportModal()"
                    class="text-white/80 hover:text-white transition-colors text-xl leading-none">
                <i class="fas fa-times"></i>
            </button>
        </div>

        {{-- Body modal --}}
        <div class="px-6 py-5">
            <p class="text-sm text-gray-500 mb-5">
                Pilih filter di bawah untuk menentukan data yang akan diekspor.
                Kosongkan semua filter untuk mengekspor seluruh metadata aktif.
            </p>

            <form id="exportForm" method="GET" action="{{ route('metadata.export') }}">

                {{-- Dropdown Produsen Data --}}
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        <i class="fas fa-building text-sky-400 mr-1"></i>
                        Produsen Data
                    </label>
                    <select name="produsen_id" id="exportProdusen"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm
                                   focus:outline-none focus:ring-2 focus:ring-sky-400 text-gray-700">
                        <option value="">— Semua Produsen —</option>
                        @foreach($produsenAll as $p)
                            <option value="{{ $p->produsen_id }}">{{ $p->nama_produsen }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-400 mt-1">Pilih produsen tertentu atau biarkan kosong untuk semua.</p>
                </div>

                {{-- Dropdown Frekuensi / Rentang Waktu --}}
                <div class="mb-5">
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        <i class="fas fa-calendar-alt text-amber-400 mr-1"></i>
                        Rentang Waktu Metadata
                    </label>
                    <select name="frekuensi" id="exportFrekuensi"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm
                                   focus:outline-none focus:ring-2 focus:ring-sky-400 text-gray-700">
                        <option value="">— Semua Frekuensi —</option>
                        <option value="5 Tahun">5 Tahun</option>
                        <option value="Tahunan">Tahunan</option>
                        <option value="Semester">Semester</option>
                        <option value="Quarter">Quarter</option>
                        <option value="Bulanan">Bulanan</option>
                    </select>
                    <p class="text-xs text-gray-400 mt-1">Filter berdasarkan frekuensi penerbitan metadata.</p>
                </div>

                {{-- Preview jumlah data (live, via AJAX) --}}
                <div id="exportPreview"
                     class="flex items-center gap-2 px-3 py-2.5 rounded-lg text-xs mb-5"
                     style="background:#f0f9ff; border:1px solid #bae6fd; color:#0369a1;">
                    <i class="fas fa-info-circle shrink-0"></i>
                    <span id="exportPreviewText">Menghitung jumlah data…</span>
                </div>

                {{-- Tombol --}}
                <div class="flex gap-3">
                    <button type="submit" id="exportBtn"
                            class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5
                                   text-sm font-semibold text-white rounded-lg transition-colors shadow-sm"
                            style="background:#059669;"
                            onmouseover="this.style.background='#047857'"
                            onmouseout="this.style.background='#059669'">
                        <i class="fas fa-download"></i>
                        Download Excel
                    </button>
                    <button type="button" onclick="closeExportModal()"
                            class="flex-1 px-4 py-2.5 text-sm font-semibold rounded-lg border
                                   border-gray-300 text-gray-600 hover:bg-gray-50 transition-colors">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════
     JAVASCRIPT
══════════════════════════════════════════════════════════════ --}}
<script>
/* ════════════════════════════════════════════════════════════════
   FILTER SERVER-SIDE — push ke URL → server query → render baru
   Dipilih karena data warehouse bisa ribuan baris.
════════════════════════════════════════════════════════════════ */
function applyFilters() {
    const url = new URL(window.location.href);
    url.searchParams.set('filter_nama',        document.getElementById('filterNama')?.value        ?? '');
    url.searchParams.set('filter_klasifikasi', document.getElementById('filterKlasifikasi')?.value ?? '');
    url.searchParams.set('filter_tipe_data',   document.getElementById('filterTipeData')?.value    ?? '');
    url.searchParams.set('filter_satuan',      document.getElementById('filterSatuan')?.value      ?? '');
    url.searchParams.set('filter_frekuensi',   document.getElementById('filterFrekuensi')?.value   ?? '');
    url.searchParams.set('filter_produsen_id', document.getElementById('filterProdusen')?.value    ?? '');
    url.searchParams.delete('page');
    window.location.href = url.toString();
}

function resetFilters() {
    const url = new URL(window.location.href);
    ['filter_nama','filter_klasifikasi','filter_tipe_data','filter_satuan',
     'filter_frekuensi','filter_produsen_id','search','page']
        .forEach(k => url.searchParams.delete(k));
    window.location.href = url.toString();
}

/* ════════════════════════════════════════════════════════════════
   MODAL EXPORT
════════════════════════════════════════════════════════════════ */
const exportModal    = document.getElementById('exportModal');
const exportProdusen = document.getElementById('exportProdusen');
const exportFrekuensi= document.getElementById('exportFrekuensi');
const previewText    = document.getElementById('exportPreviewText');

function openExportModal() {
    exportModal.classList.remove('hidden');
    exportModal.classList.add('flex');
    fetchPreviewCount();
}

function closeExportModal() {
    exportModal.classList.add('hidden');
    exportModal.classList.remove('flex');
}

// Tutup modal jika klik backdrop
exportModal.addEventListener('click', function(e) {
    if (e.target === exportModal) closeExportModal();
});

// ESC untuk tutup modal
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeExportModal(); });

/* ── Preview Count (AJAX) ──────────────────────────────────── */
// Hitung estimasi data yang akan diekspor secara live saat user
// memilih filter — memberikan konfirmasi visual sebelum download.

let previewDebounce = null;

function fetchPreviewCount() {
    clearTimeout(previewDebounce);
    previewText.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Menghitung…';

    previewDebounce = setTimeout(async () => {
        try {
            const params = new URLSearchParams();
            if (exportProdusen.value)  params.set('produsen_id', exportProdusen.value);
            if (exportFrekuensi.value) params.set('frekuensi',   exportFrekuensi.value);
            params.set('count_only', '1');

            const res  = await fetch('{{ route("metadata.export.count") }}?' + params.toString(), {
                headers: { 'Accept': 'application/json' },
            });
            const json = await res.json();

            if (json.count === 0) {
                previewText.textContent = 'Tidak ada data sesuai filter yang dipilih.';
                document.getElementById('exportPreview').style.cssText =
                    'background:#fef2f2; border-color:#fecaca; color:#b91c1c;';
                document.getElementById('exportBtn').disabled = true;
            } else {
                previewText.textContent = `${json.count.toLocaleString('id-ID')} metadata akan diekspor.`;
                document.getElementById('exportPreview').style.cssText =
                    'background:#f0f9ff; border-color:#bae6fd; color:#0369a1;';
                document.getElementById('exportBtn').disabled = false;
            }
        } catch {
            previewText.textContent = 'Gagal menghitung data.';
        }
    }, 350);
}

exportProdusen.addEventListener('change', fetchPreviewCount);
exportFrekuensi.addEventListener('change', fetchPreviewCount);

/* ── Loading state saat submit export ───────────────────────── */
document.getElementById('exportForm').addEventListener('submit', function() {
    const btn = document.getElementById('exportBtn');
    btn.disabled  = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyiapkan file…';
    // Re-enable setelah 8 detik (antisipasi download lama)
    setTimeout(() => {
        btn.disabled  = false;
        btn.innerHTML = '<i class="fas fa-download"></i> Download Excel';
    }, 8000);
});
</script>

@endsection