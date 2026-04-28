@extends('layouts.main')

@section('content')
<div class="mt-2 bg-white rounded-xl shadow p-6">

    {{-- BREADCRUMB --}}
    <div class="flex items-center gap-2 text-sm text-gray-400 mb-5">
        <a href="{{ route('data.index') }}" class="hover:text-sky-500 transition-colors">Data</a>
        <i class="fas fa-chevron-right text-xs"></i>
        <span class="text-gray-600 font-medium">Edit Template</span>
    </div>

    @php
        $jenis      = $fp['jenis_template'] ?? 'metadata';
        $jenisLabel = ['metadata' => 'Metadata', 'klasifikasi' => 'Klasifikasi', 'wilayah' => 'Wilayah'][$jenis] ?? $jenis;
        $jenisColor = ['metadata' => 'sky', 'klasifikasi' => 'violet', 'wilayah' => 'emerald'][$jenis] ?? 'sky';
        $jenisIcon  = ['metadata' => 'fa-database', 'klasifikasi' => 'fa-tags', 'wilayah' => 'fa-map-marker-alt'][$jenis] ?? 'fa-database';
    @endphp

    {{-- HEADER --}}
    <div class="mb-6 flex items-start justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-800">Edit Template</h1>
            <p class="text-sm text-gray-400 mt-1">Ubah nama dan isi metadata template Anda</p>
        </div>
        <span class="px-3 py-1.5 bg-{{ $jenisColor }}-50 text-{{ $jenisColor }}-600 border border-{{ $jenisColor }}-100 text-xs font-semibold rounded-full">
            <i class="fas {{ $jenisIcon }} mr-1"></i> Jenis: {{ $jenisLabel }}
        </span>
    </div>

    {{-- SUCCESS / ERROR ALERT --}}
    @if(session('success'))
        <div class="mb-4 flex items-center gap-3 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
            <i class="fas fa-check-circle text-green-500 shrink-0"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- FORM --}}
    <form id="editForm" action="{{ route('template.update', $tampilan->tampilan_id) }}" method="POST">
        @csrf
        @method('PUT')

        {{-- ── SECTION 1: NAMA TEMPLATE ── --}}
        <div class="p-5 border border-gray-200 rounded-xl mb-4 bg-gray-50/40">
            <h2 class="text-sm font-bold text-gray-700 mb-3 flex items-center gap-2">
                <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-gray-500 text-white text-xs font-bold">1</span>
                Nama Template
            </h2>
            <input type="text"
                   name="nama_tampilan"
                   id="inputNamaTampilan"
                   value="{{ old('nama_tampilan', $tampilan->nama_tampilan) }}"
                   maxlength="100"
                   required
                   placeholder="Nama template..."
                   class="w-full max-w-lg border border-gray-300 rounded-lg px-3 py-2.5 text-sm
                          focus:outline-none focus:ring-2 focus:ring-violet-400 bg-white">
            @error('nama_tampilan')
                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
            @enderror
        </div>

        {{-- ── SECTION 2: INFO FILTER TERSIMPAN (read-only) ──
        @if(!empty($fp) || $existingLocations->isNotEmpty())
        <div class="p-5 border border-gray-200 rounded-xl mb-4 bg-gray-50/40">
            <h2 class="text-sm font-bold text-gray-700 mb-3 flex items-center gap-2">
                <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-gray-400 text-white text-xs font-bold">2</span>
                Konfigurasi Template
                <span class="text-xs font-normal text-gray-400">(tidak dapat diubah)</span>
            </h2>
            <div class="flex flex-wrap gap-2 text-xs">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-gray-100 text-gray-600 rounded-full border border-gray-200">
                    <i class="fas {{ $jenisIcon }} text-gray-400"></i>
                    Jenis: {{ $jenisLabel }}
                </span>
                @if($existingLocations->isNotEmpty())
                    @foreach($existingLocations as $loc)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-emerald-50 text-emerald-700 rounded-full border border-emerald-100">
                            <i class="fas fa-map-marker-alt text-emerald-400 text-xs"></i>
                            {{ $loc->nama_wilayah }}
                        </span>
                    @endforeach
                @endif
                @if(!empty($fp['klasifikasi']))
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-violet-50 text-violet-700 rounded-full border border-violet-100">
                        <i class="fas fa-tags text-violet-400 text-xs"></i>
                        {{ $fp['klasifikasi'] }}
                    </span>
                @endif
            </div>
            <p class="mt-2 text-xs text-gray-400">
                <i class="fas fa-info-circle mr-1"></i>
                Untuk mengubah jenis/wilayah/klasifikasi, hapus template ini dan buat yang baru.
            </p>
        </div>
        @endif --}}

        {{-- ── SECTION 3: KELOLA METADATA ── --}}
        <div class="p-5 border border-gray-200 rounded-xl mb-4">
            <h2 class="text-sm font-bold text-gray-700 mb-4 flex items-center gap-2">
                <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-violet-500 text-white text-xs font-bold">2</span>
                Isi Template
            </h2>

            {{-- Pencarian metadata baru --}}
            <div class="mb-4">
                <label class="block text-xs text-gray-500 font-medium mb-1.5">
                    <i class="fas fa-plus-circle mr-1 text-gray-400"></i> Tambah Metadata
                </label>
                <div class="relative" id="metaDropWrap">
                    <input type="text" id="metaSearchInput"
                           placeholder="Ketik untuk mencari metadata yang ingin ditambahkan..."
                           autocomplete="off"
                           oninput="onMetaInput()"
                           onfocus="onMetaFocus()"
                           class="w-full border border-gray-300 rounded-lg pl-8 pr-4 py-2.5 text-sm
                                  focus:outline-none focus:ring-2 focus:ring-violet-400 bg-white">
                    <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                    <div id="metaDropList"
                         class="hidden absolute z-20 w-full mt-1 bg-white border border-gray-200
                                rounded-xl shadow-xl max-h-64 overflow-y-auto"></div>
                </div>
            </div>

            {{-- Daftar metadata yang sudah terpilih --}}
            <div class="mb-2 flex items-center justify-between">
                <p class="text-xs font-semibold text-gray-600">
                    Metadata dalam template:
                    <span id="metaCountBadge"
                          class="ml-1 inline-block px-2 py-0.5 bg-violet-100 text-violet-700 rounded-full text-xs font-bold">
                        {{ $existingMetadata->count() }}
                    </span>
                </p>
                <button type="button" onclick="clearAllMeta()"
                        class="text-xs text-red-400 hover:text-red-600 hover:underline transition-colors">
                    <i class="fas fa-trash mr-1"></i> Hapus Semua
                </button>
            </div>

            {{-- Tabel metadata terpilih --}}
            <div class="border border-gray-200 rounded-xl overflow-hidden">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider border-b">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Metadata</th>
                            <th class="px-4 py-3 font-semibold w-32">Klasifikasi</th>
                            <th class="px-4 py-3 font-semibold w-28">Frekuensi</th>
                            <th class="px-4 py-3 w-12 text-center font-semibold">Hapus</th>
                        </tr>
                    </thead>
                    <tbody id="selectedMetaTbody" class="divide-y divide-gray-100">
                        {{-- Diisi oleh JS dari selectedMeta state --}}
                        <tr id="emptyRow">
                            <td colspan="4" class="px-4 py-8 text-center text-gray-400 text-sm">
                                <i class="fas fa-inbox text-2xl text-gray-200 block mb-2"></i>
                                Belum ada metadata — gunakan pencarian di atas
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Hidden inputs — diisi JS sebelum submit --}}
            <div id="hiddenMetaInputs"></div>
        </div>

        {{-- TOMBOL AKSI --}}
        <div class="flex items-center justify-between gap-3 pt-2">
            <a href="{{ route('data.index') }}"
               class="border border-gray-300 text-gray-500 hover:bg-gray-50 px-5 py-2.5
                      rounded-lg text-sm transition-colors flex items-center gap-2">
                <i class="fas fa-arrow-left text-xs"></i> Batal
            </a>
            <div class="flex gap-2">
                {{-- Preview ringkasan sebelum simpan --}}
                <button type="button" onclick="openConfirmModal()"
                    class="px-5 py-2.5 text-white text-sm font-semibold rounded-lg
                           flex items-center gap-2 transition-colors shadow-md"
                    style="background:#8b5cf6;"
                    onmouseover="this.style.background='#7c3aed'"
                    onmouseout="this.style.background='#8b5cf6'">
                    <i class="fas fa-save"></i> Simpan Perubahan
                </button>
            </div>
        </div>
    </form>
</div>

{{-- ═══ MODAL KONFIRMASI SIMPAN ═══ --}}
<div id="confirmModal"
     style="position:fixed;inset:0;z-index:50;background:rgba(0,0,0,0.45);
            display:none;align-items:center;justify-content:center;padding:1rem;">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md">
        <div class="px-6 py-4 rounded-t-xl" style="background:linear-gradient(135deg,#8b5cf6,#6d28d9);">
            <div class="flex items-center justify-between">
                <h3 class="text-white font-bold text-base flex items-center gap-2">
                    <i class="fas fa-save"></i> Konfirmasi Perubahan
                </h3>
                <button onclick="closeConfirmModal()"
                        class="text-purple-200 hover:text-white text-2xl leading-none">×</button>
            </div>
        </div>
        <div class="p-6 space-y-3">
            <div class="p-3 bg-gray-50 border border-gray-200 rounded-lg">
                <p class="text-xs text-gray-500 font-semibold uppercase tracking-wide mb-1">Nama Template</p>
                <p class="text-sm text-gray-800 font-bold" id="confirmNama">—</p>
            </div>
            <div class="p-3 bg-violet-50 border border-violet-100 rounded-lg">
                <p class="text-xs text-violet-600 font-semibold uppercase tracking-wide mb-1">
                    Metadata yang Disimpan
                </p>
                <p class="text-sm font-bold text-violet-800">
                    <span id="confirmMetaCount">0</span> metadata
                </p>
            </div>
            <p class="text-xs text-gray-400">
                <i class="fas fa-exclamation-triangle text-amber-400 mr-1"></i>
                Metadata yang dihapus dari template tidak akan bisa dikembalikan secara otomatis.
            </p>
        </div>
        <div class="px-6 py-4 border-t bg-gray-50 rounded-b-xl flex justify-end gap-2">
            <button onclick="closeConfirmModal()"
                class="border border-gray-300 text-gray-500 hover:bg-gray-100 px-4 py-2 rounded-lg text-sm">
                Batal
            </button>
            <button onclick="doSubmit()"
                class="px-5 py-2 text-white text-sm font-semibold rounded-lg flex items-center gap-2"
                style="background:#8b5cf6;"
                onmouseover="this.style.background='#7c3aed'"
                onmouseout="this.style.background='#8b5cf6'">
                <i class="fas fa-save"></i> Ya, Simpan
            </button>
        </div>
    </div>
</div>

<script>
// ═══════════════════════════════════════════════════════════════
// STATE — map metadata yang sedang ada di template
// { metadata_id (int): { metadata_id, nama, klasifikasi, satuan_data, frekuensi_penerbitan } }
// ═══════════════════════════════════════════════════════════════
let selectedMeta = {};

// Pre-populate dari data PHP (metadata yang sudah ada)
@foreach($existingMetadata as $m)
selectedMeta[{{ $m->metadata_id }}] = {
    metadata_id:          {{ $m->metadata_id }},
    nama:                 @json($m->nama),
    klasifikasi:          @json($m->klasifikasi ?? ''),
    satuan_data:          @json($m->satuan_data ?? ''),
    frekuensi_penerbitan: @json($m->frekuensi_penerbitan ?? ''),
};
@endforeach

// Cache untuk dropdown pencarian
const allMetaCache = @json($allMetadata);
let searchTimeout  = null;

// ═══════════════════════════════════════════════════════════════
// RENDER TABEL — menampilkan metadata yang terpilih
// ═══════════════════════════════════════════════════════════════
function renderSelectedTable() {
    const tbody    = document.getElementById('selectedMetaTbody');
    const emptyRow = document.getElementById('emptyRow');
    const items    = Object.values(selectedMeta);
    const count    = items.length;

    // Update badge count
    document.getElementById('metaCountBadge').textContent = count;

    if (!count) {
        tbody.innerHTML = `<tr id="emptyRow">
            <td colspan="4" class="px-4 py-8 text-center text-gray-400 text-sm">
                <i class="fas fa-inbox text-2xl text-gray-200 block mb-2"></i>
                Belum ada metadata — gunakan pencarian di atas
            </td>
        </tr>`;
        return;
    }

    tbody.innerHTML = items.map(m => `
        <tr class="hover:bg-red-50/30 transition-colors group" id="mrow-${m.metadata_id}">
            <td class="px-4 py-3">
                <p class="text-xs font-semibold text-gray-800">${escH(m.nama)}</p>
                ${m.satuan_data
                    ? `<p class="text-xs text-gray-400">${escH(m.satuan_data)}</p>`
                    : ''}
            </td>
            <td class="px-4 py-3 text-xs text-gray-500">${escH(m.klasifikasi || '—')}</td>
            <td class="px-4 py-3">
                ${m.frekuensi_penerbitan
                    ? `<span class="inline-block px-2 py-0.5 bg-gray-100 text-gray-600
                               text-xs rounded-full">${escH(m.frekuensi_penerbitan)}</span>`
                    : '<span class="text-gray-400 text-xs">—</span>'}
            </td>
            <td class="px-4 py-3 text-center">
                <button type="button" onclick="removeMeta(${m.metadata_id})"
                    class="w-7 h-7 inline-flex items-center justify-center rounded-lg
                           text-gray-400 hover:text-red-500 hover:bg-red-50
                           transition-colors group-hover:text-red-400"
                    title="Hapus dari template">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </td>
        </tr>`
    ).join('');
}

// ═══════════════════════════════════════════════════════════════
// DROPDOWN PENCARIAN METADATA
// ═══════════════════════════════════════════════════════════════
function onMetaFocus() {
    const box = document.getElementById('metaDropList');
    if (!box.classList.contains('hidden')) return;
    renderMetaDrop(allMetaCache);
}

function onMetaInput() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        const q = document.getElementById('metaSearchInput').value.trim().toLowerCase();
        const filtered = q
            ? allMetaCache.filter(m => m.nama.toLowerCase().includes(q)
                || (m.klasifikasi || '').toLowerCase().includes(q))
            : allMetaCache;
        renderMetaDrop(filtered);
    }, 150);
}

function renderMetaDrop(items) {
    const box = document.getElementById('metaDropList');

    if (!items.length) {
        box.innerHTML = `<p class="px-4 py-3 text-xs text-gray-400 text-center">
            Tidak ada metadata ditemukan
        </p>`;
        box.classList.remove('hidden');
        return;
    }

    // Pisahkan: sudah ada di template vs belum
    const alreadyIds = new Set(Object.keys(selectedMeta).map(Number));

    box.innerHTML = items.map(m => {
        const isIn = alreadyIds.has(m.metadata_id);
        return `<button type="button"
            onclick="${isIn ? '' : `addMeta(${m.metadata_id}, '${escAttr(m.nama)}', '${escAttr(m.klasifikasi||'')}', '${escAttr(m.satuan_data||'')}', '${escAttr(m.frekuensi_penerbitan||'')}')`}"
            class="w-full text-left px-4 py-2.5 flex items-start gap-2.5 border-b border-gray-50
                   last:border-0 transition-colors
                   ${isIn ? 'opacity-50 cursor-not-allowed bg-gray-50' : 'hover:bg-violet-50 cursor-pointer'}">
            <span class="mt-0.5 shrink-0">
                ${isIn
                    ? '<i class="fas fa-check-circle text-emerald-400 text-xs"></i>'
                    : '<i class="far fa-circle text-gray-300 text-xs"></i>'}
            </span>
            <span class="flex flex-col gap-0.5 min-w-0 flex-1">
                <span class="font-medium text-xs text-gray-800 leading-snug">${escH(m.nama)}</span>
                <span class="text-xs text-gray-400 truncate">
                    ${escH(m.klasifikasi || '')}
                    ${m.satuan_data ? ' · ' + escH(m.satuan_data) : ''}
                    ${m.frekuensi_penerbitan ? ' · ' + escH(m.frekuensi_penerbitan) : ''}
                </span>
            </span>
            ${isIn
                ? '<span class="shrink-0 text-xs text-emerald-500 font-medium">Sudah ada</span>'
                : '<span class="shrink-0 text-xs text-violet-400 font-medium">+ Tambah</span>'}
        </button>`;
    }).join('');

    box.classList.remove('hidden');
}

// ═══════════════════════════════════════════════════════════════
// TAMBAH / HAPUS METADATA
// ═══════════════════════════════════════════════════════════════
function addMeta(id, nama, klasifikasi, satuan, frekuensi) {
    selectedMeta[id] = { metadata_id: id, nama, klasifikasi, satuan_data: satuan, frekuensi_penerbitan: frekuensi };
    renderSelectedTable();

    // Refresh dropdown agar row yang baru ditambah berubah jadi "Sudah ada"
    const q = document.getElementById('metaSearchInput').value.trim().toLowerCase();
    const filtered = q
        ? allMetaCache.filter(m => m.nama.toLowerCase().includes(q)
            || (m.klasifikasi || '').toLowerCase().includes(q))
        : allMetaCache;
    renderMetaDrop(filtered);

    // Animasi highlight baris yang baru ditambah
    requestAnimationFrame(() => {
        const row = document.getElementById(`mrow-${id}`);
        if (row) {
            row.style.background = '#f5f3ff';
            setTimeout(() => { row.style.background = ''; }, 1000);
        }
    });
}

function removeMeta(id) {
    delete selectedMeta[id];
    renderSelectedTable();

    // Refresh dropdown
    const q = document.getElementById('metaSearchInput').value.trim().toLowerCase();
    const filtered = q
        ? allMetaCache.filter(m => m.nama.toLowerCase().includes(q)
            || (m.klasifikasi || '').toLowerCase().includes(q))
        : allMetaCache;
    renderMetaDrop(filtered);
}

function clearAllMeta() {
    if (!Object.keys(selectedMeta).length) return;
    if (!confirm('Hapus semua metadata dari template? Anda perlu menambahkan ulang secara manual.')) return;
    selectedMeta = {};
    renderSelectedTable();
}

// ═══════════════════════════════════════════════════════════════
// SUBMIT — isi hidden inputs lalu submit form
// ═══════════════════════════════════════════════════════════════
function openConfirmModal() {
    const count = Object.keys(selectedMeta).length;
    if (!count) {
        alert('Template harus memiliki minimal 1 metadata.');
        return;
    }

    const nama = document.getElementById('inputNamaTampilan').value.trim();
    if (!nama) {
        document.getElementById('inputNamaTampilan').focus();
        alert('Nama template tidak boleh kosong.');
        return;
    }

    document.getElementById('confirmNama').textContent      = nama;
    document.getElementById('confirmMetaCount').textContent = count;
    document.getElementById('confirmModal').style.display   = 'flex';
}

function closeConfirmModal() {
    document.getElementById('confirmModal').style.display = 'none';
}

function doSubmit() {
    // Isi hidden inputs metadata_ids[]
    const hiddenDiv = document.getElementById('hiddenMetaInputs');
    hiddenDiv.innerHTML = Object.keys(selectedMeta)
        .map(id => `<input type="hidden" name="metadata_ids[]" value="${id}">`)
        .join('');

    closeConfirmModal();
    document.getElementById('editForm').submit();
}

// ═══════════════════════════════════════════════════════════════
// UTILS
// ═══════════════════════════════════════════════════════════════
function escH(s) {
    const d = document.createElement('div');
    d.innerText = s || '';
    return d.innerHTML;
}

// Untuk inline onclick attribute — escape single quote
function escAttr(s) {
    return (s || '').replace(/\\/g, '\\\\').replace(/'/g, "\\'");
}

// Tutup dropdown jika klik di luar
document.addEventListener('click', e => {
    const wrap = document.getElementById('metaDropWrap');
    const drop = document.getElementById('metaDropList');
    if (wrap && drop && !wrap.contains(e.target)) {
        drop.classList.add('hidden');
    }
    if (e.target === document.getElementById('confirmModal')) {
        closeConfirmModal();
    }
});

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeConfirmModal();
});

// Render tabel awal saat halaman dimuat
document.addEventListener('DOMContentLoaded', () => {
    renderSelectedTable();
});
</script>

@endsection