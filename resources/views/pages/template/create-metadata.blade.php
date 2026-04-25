@extends('layouts.main')

@section('content')

{{-- ── Aksen warna untuk partial _preview-table (sky/biru) ── --}}
<style>
    .preview-badge        { background:#f0f9ff; color:#0284c7; border-color:#bae6fd; }
    .preview-btn-primary  { background:#0ea5e9; }
    .preview-btn-primary:hover { background:#0284c7; }
    #accentBar            { background:#0ea5e9; }
    .preview-modal-header { background: linear-gradient(135deg, #0ea5e9, #0369a1); }
    .preview-summary-box  { background:#f0f9ff; border-color:#bae6fd; }
    .preview-summary-icon { background:#bae6fd; color:#0284c7; }
    .preview-summary-text { color:#0369a1; }
    .preview-summary-sub  { color:#0284c7; }
    #selectionBarPreview  { background:#f0f9ff; border-color:#bae6fd; }
    #selectionBarPreview p { color:#0369a1; }
    #selectionBarPreview button { color:#0284c7; }
</style>

<div class="mt-2 bg-white rounded-xl shadow p-6">

    {{-- BREADCRUMB --}}
    <div class="flex items-center gap-2 text-sm text-gray-400 mb-5">
        <a href="{{ route('data.index') }}" class="hover:text-sky-500 transition-colors">Data</a>
        <i class="fas fa-chevron-right text-xs"></i>
        <a href="{{ route('template.create') }}" class="hover:text-sky-500 transition-colors">Buat Template</a>
        <i class="fas fa-chevron-right text-xs"></i>
        <span class="text-gray-600 font-medium">Template Metadata</span>
    </div>

    <div class="mb-6 flex items-start justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-800">Buat Template Metadata</h1>
            <p class="text-sm text-gray-400 mt-1">Pilih metadata dan wilayah yang ingin Anda tampilkan</p>
        </div>
        <span class="px-3 py-1.5 bg-sky-50 text-sky-600 border border-sky-100 text-xs font-semibold rounded-full">
            <i class="fas fa-database mr-1"></i> Jenis: Metadata
        </span>
    </div>

    {{-- ═══ SECTION 1: FORM FILTER ═══ --}}
    <div class="p-5 border border-gray-200 rounded-xl mb-2">
        <h2 class="text-sm font-bold text-gray-700 mb-4">
            <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-sky-500 text-white text-xs font-bold mr-2">1</span>
            Pilih Metadata &amp; Wilayah
        </h2>

        <div class="space-y-4">

            {{-- ── METADATA multi-select ── --}}
            <div>
                <label class="block text-xs text-gray-500 font-medium mb-1">
                    <i class="fas fa-database mr-1 text-gray-400"></i> Metadata
                    <span class="text-red-500">*</span>
                </label>
                <div class="relative" id="metaDropWrap">
                    <input type="text" id="metaSearch"
                           placeholder="Ketik untuk mencari metadata..."
                           autocomplete="off"
                           oninput="onMetaSearchInput()"
                           onfocus="onMetaSearchFocus()"
                           class="w-full border border-gray-300 rounded-lg pl-8 pr-4 py-2.5 text-sm
                                  focus:outline-none focus:ring-2 focus:ring-sky-400 transition-shadow">
                    <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>

                    {{-- Dropdown list --}}
                    <div id="metaDropList"
                         class="hidden absolute z-20 w-full mt-1 bg-white border border-gray-200
                                rounded-xl shadow-xl max-h-72 overflow-y-auto">
                        {{-- Diisi JS — tidak ada spinner, langsung render dari cache --}}
                        <p class="px-4 py-3 text-xs text-gray-400 text-center">
                            <i class="fas fa-circle-notch fa-spin mr-1"></i> Memuat…
                        </p>
                    </div>
                </div>

                {{-- Chips metadata terpilih --}}
                <div id="selectedMetaChips" class="flex flex-wrap gap-1.5 mt-2 min-h-6"></div>
            </div>

            {{-- ── WILAYAH cascade ── --}}
            <div>
                <label class="block text-xs text-gray-500 font-medium mb-2">
                    <i class="fas fa-map-marker-alt mr-1 text-gray-400"></i> Wilayah
                    <span class="text-gray-400 font-normal">(opsional — pilih hingga level yang diinginkan)</span>
                </label>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">

                    {{-- PROVINSI --}}
                    <div>
                        <label class="block text-xs text-gray-400 font-semibold mb-1 uppercase tracking-wide">Provinsi</label>
                        <div class="relative" id="m_wrapProvinsi">
                            <select id="m_selectProvinsi"
                                    onchange="mSelectFromNative('provinsi', this.value, this.options[this.selectedIndex].text)"
                                    class="w-full border border-gray-300 rounded-lg pl-7 pr-3 py-2 text-sm
                                           focus:outline-none focus:ring-2 focus:ring-sky-400 bg-white appearance-none">
                                <option value="">— Pilih Provinsi —</option>
                                @foreach($provinsiList as $p)
                                    <option value="{{ $p->location_id }}">{{ $p->nama_wilayah }}</option>
                                @endforeach
                            </select>
                            <i class="fas fa-map-marker-alt absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                        </div>
                        <input type="hidden" id="m_valProvinsi">
                    </div>

                    {{-- KABUPATEN --}}
                    <div>
                        <label class="block text-xs text-gray-400 font-semibold mb-1 uppercase tracking-wide">Kabupaten / Kota</label>
                        <div class="relative" id="m_wrapKabupaten">
                            <select id="m_selectKabupaten" disabled
                                    onchange="mSelectFromNative('kabupaten', this.value, this.options[this.selectedIndex].text)"
                                    class="w-full border border-gray-200 rounded-lg pl-7 pr-3 py-2 text-sm
                                           focus:outline-none focus:ring-2 focus:ring-sky-400 bg-gray-100 text-gray-400 cursor-not-allowed appearance-none">
                                <option value="">— Pilih Provinsi dulu —</option>
                            </select>
                            <i class="fas fa-map-marker-alt absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                        </div>
                        <input type="hidden" id="m_valKabupaten">
                    </div>

                    {{-- KECAMATAN --}}
                    <div>
                        <label class="block text-xs text-gray-400 font-semibold mb-1 uppercase tracking-wide">Kecamatan</label>
                        <div class="relative" id="m_wrapKecamatan">
                            <select id="m_selectKecamatan" disabled
                                    onchange="mSelectFromNative('kecamatan', this.value, this.options[this.selectedIndex].text)"
                                    class="w-full border border-gray-200 rounded-lg pl-7 pr-3 py-2 text-sm
                                           focus:outline-none focus:ring-2 focus:ring-sky-400 bg-gray-100 text-gray-400 cursor-not-allowed appearance-none">
                                <option value="">— Pilih Kabupaten dulu —</option>
                            </select>
                            <i class="fas fa-map-marker-alt absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                        </div>
                        <input type="hidden" id="m_valKecamatan">
                    </div>

                    {{-- DESA --}}
                    <div>
                        <label class="block text-xs text-gray-400 font-semibold mb-1 uppercase tracking-wide">Desa / Kelurahan</label>
                        <div class="relative" id="m_wrapDesa">
                            <select id="m_selectDesa" disabled
                                    onchange="mSelectFromNative('desa', this.value, this.options[this.selectedIndex].text)"
                                    class="w-full border border-gray-200 rounded-lg pl-7 pr-3 py-2 text-sm
                                           focus:outline-none focus:ring-2 focus:ring-sky-400 bg-gray-100 text-gray-400 cursor-not-allowed appearance-none">
                                <option value="">— Pilih Kecamatan dulu —</option>
                            </select>
                            <i class="fas fa-map-marker-alt absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                        </div>
                        <input type="hidden" id="m_valDesa">
                    </div>
                </div>

                {{-- Badge wilayah terpilih --}}
                <div id="m_selectedWilayahBadge"
                     class="hidden mt-3 flex items-center gap-2 p-2.5 bg-sky-50 border border-sky-200 rounded-lg w-fit">
                    <i class="fas fa-map-marker-alt text-sky-500 text-xs"></i>
                    <span class="text-xs text-sky-700 font-semibold" id="m_badgeNama">—</span>
                    <span class="text-xs text-sky-500" id="m_badgeLevel">—</span>
                </div>
            </div>

            <button type="button" onclick="loadMetadataPreview()"
                class="px-5 py-2.5 bg-sky-500 hover:bg-sky-600 active:bg-sky-700 text-white text-sm font-semibold rounded-lg
                       shadow-md shadow-sky-400/30 flex items-center gap-2 transition-all">
                <i class="fas fa-search"></i> Pilih &amp; Tampilkan
            </button>
        </div>
    </div>

    {{-- ═══ SECTION 2: PREVIEW TABLE ═══ --}}
    <div id="sectionResult" class="hidden mt-6">

        {{-- TAB SWITCHER --}}
        <div class="border-b border-gray-200 mb-4">
            <div class="flex gap-1 overflow-x-auto" id="freqTabs">
                @foreach(['dekade' => 'Dekade', 'tahunan' => 'Tahunan', 'semester' => 'Semester', 'kuartal' => 'Kuartal', 'bulanan' => 'Bulanan'] as $key => $label)
                    <button type="button"
                        id="tab-{{ $key }}"
                        onclick="switchTab('{{ $key }}')"
                        class="tab-btn shrink-0 px-4 py-2.5 text-xs font-semibold border-b-2 transition-colors
                               border-transparent text-gray-400 cursor-not-allowed"
                        disabled>
                        {{ $label }}
                        <span id="tab-count-{{ $key }}"
                              class="ml-1.5 text-xs font-bold px-1.5 py-0.5 rounded-full bg-gray-100 text-gray-400">0</span>
                    </button>
                @endforeach
            </div>
        </div>

        {{-- PERIODE FILTER --}}
        <div id="periodeFilter" class="mb-4 p-3 bg-gray-50 rounded-lg border border-gray-200 hidden">
            <div class="flex flex-wrap items-center gap-3 text-sm">

                {{-- Dekade / Tahunan: hanya period_from – period_to --}}
                <div id="periodeSimple" class="flex items-center gap-2 hidden">
                    <label class="text-xs text-gray-500 font-medium" id="periodeSimpleLabel">Rentang Tahun:</label>
                    <input type="number" id="periodFromSimple" placeholder="Dari" min="1900" max="2100"
                           class="border border-gray-300 rounded-md px-2 py-1.5 text-xs w-24 focus:outline-none focus:ring-2 focus:ring-emerald-400">
                    <span class="text-gray-400">—</span>
                    <input type="number" id="periodToSimple" placeholder="Sampai" min="1900" max="2100"
                           class="border border-gray-300 rounded-md px-2 py-1.5 text-xs w-24 focus:outline-none focus:ring-2 focus:ring-emerald-400">
                </div>

                {{-- Semester / Kuartal / Bulanan: tahun + sub-periode --}}
                <div id="periodeComplex" class="flex flex-wrap items-center gap-2 hidden">
                    <label class="text-xs text-gray-500 font-medium">Tahun:</label>
                    <input type="number" id="yearFrom" placeholder="Dari" min="1900" max="2100"
                           class="border border-gray-300 rounded-md px-2 py-1.5 text-xs w-20 focus:outline-none focus:ring-2 focus:ring-emerald-400">
                    <span class="text-gray-400">—</span>
                    <input type="number" id="yearTo" placeholder="Sampai" min="1900" max="2100"
                           class="border border-gray-300 rounded-md px-2 py-1.5 text-xs w-20 focus:outline-none focus:ring-2 focus:ring-emerald-400">
                    <label class="text-xs text-gray-500 font-medium ml-2" id="periodeLabel">Periode:</label>
                    <input type="number" id="periodFrom" placeholder="Dari" min="1" max="12"
                           class="border border-gray-300 rounded-md px-2 py-1.5 text-xs w-16 focus:outline-none focus:ring-2 focus:ring-emerald-400">
                    <span class="text-gray-400">—</span>
                    <input type="number" id="periodTo" placeholder="Sampai" min="1" max="12"
                           class="border border-gray-300 rounded-md px-2 py-1.5 text-xs w-16 focus:outline-none focus:ring-2 focus:ring-emerald-400">
                </div>

                <button type="button" onclick="applyPeriodeFilter()"
                    class="px-3 py-1.5 bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-semibold rounded-md transition-colors flex items-center gap-1">
                    <i class="fas fa-search"></i> Tampilkan
                </button>
                <button type="button" onclick="resetPeriodeFilter()"
                    class="px-3 py-1.5 border border-gray-300 text-gray-500 hover:bg-gray-100 text-xs font-semibold rounded-md transition-colors">
                    Reset
                </button>
            </div>
        </div>

        {{-- Info bar --}}
        <div class="flex items-center justify-between mb-3">
            <div>
                <h3 class="font-bold text-gray-700 text-sm">Hasil Metadata</h3>
                <p class="text-xs text-gray-400 mt-0.5" id="resultDesc">—</p>
            </div>
            <span id="totalBadge"
                  class="text-xs bg-emerald-50 text-emerald-600 border border-emerald-100 px-2.5 py-1 rounded-full font-semibold">
                0 baris
            </span>
        </div>

        {{-- Tabel --}}
        <div class="border border-gray-200 rounded-xl overflow-hidden">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider border-b">
                    <tr>
                        <th class="px-4 py-3 w-10">
                            <input type="checkbox" id="checkAll" onchange="toggleAll(this)"
                                   class="rounded border-gray-300 cursor-pointer">
                        </th>
                        <th class="px-4 py-3 font-semibold">Metadata – Wilayah</th>
                        <th class="px-4 py-3 font-semibold w-36 text-center">Detail Wilayah</th>
                        <th class="px-4 py-3 font-semibold w-28 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody id="resultTbody" class="divide-y divide-gray-100"></tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div id="paginWrap" class="hidden mt-3 flex items-center justify-between text-xs text-gray-500">
            <span id="paginInfo"></span>
            <div id="paginBtns" class="flex gap-1"></div>
        </div>

        {{-- Seleksi bar --}}
        <div id="selBar" class="mt-3 hidden items-center justify-between px-4 py-2.5 rounded-lg"
             style="background:#f0fdf4; border:1px solid #bbf7d0;">
            <p class="font-medium text-emerald-700 flex items-center gap-2 text-sm">
                <i class="fas fa-check-square"></i>
                <span id="selCount">0 metadata dipilih</span>
            </p>
            <button onclick="clearAllSel()" class="text-xs font-medium text-emerald-600 hover:underline">
                <i class="fas fa-times mr-1"></i> Batalkan Pilihan
            </button>
        </div>

        {{-- Pengaturan urutan --}}
        <div class="mt-5 p-4 bg-gray-50 border border-gray-200 rounded-xl">
            <p class="text-xs font-bold text-gray-600 mb-3 flex items-center gap-1.5">
                <i class="fas fa-sort-amount-down text-gray-400"></i> Pengaturan Urutan Tampilan
            </p>
            <div class="flex flex-wrap items-center gap-4">
                <label class="flex items-center gap-2 cursor-pointer select-none">
                    <input type="checkbox" id="chkKlasifikasi" class="rounded border-gray-300">
                    <span class="text-xs text-gray-600">Atur berdasarkan Klasifikasi</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer select-none">
                    <input type="checkbox" id="chkWilayah" class="rounded border-gray-300">
                    <span class="text-xs text-gray-600">Atur berdasarkan Wilayah</span>
                </label>
                <button type="button" onclick="terapkanUrutan()"
                    class="px-4 py-1.5 bg-gray-700 hover:bg-gray-800 text-white text-xs font-semibold rounded-lg transition-colors">
                    Terapkan
                </button>
            </div>
        </div>

        {{-- Tombol Simpan --}}
        <div class="mt-5 flex justify-end gap-3">
            <a href="{{ route('template.create') }}"
               class="border border-gray-300 text-gray-500 hover:bg-gray-50 px-4 py-2.5 rounded-lg text-sm transition-colors">
                Batal
            </a>
            <button type="button" onclick="openSaveModal()"
                class="px-6 py-2.5 text-white text-sm font-semibold rounded-lg flex items-center gap-2 transition-colors"
                style="background:#8b5cf6;"
                onmouseover="this.style.background='#7c3aed'"
                onmouseout="this.style.background='#8b5cf6'">
                <i class="fas fa-save"></i> Simpan Template
            </button>
        </div>
    </div>
</div>

{{-- HIDDEN FORM --}}
<form id="formSaveTemplateHidden" action="{{ route('template.store') }}" method="POST" class="hidden">
    @csrf
    <input type="hidden" name="jenis_template" value="metadata">
    <input type="hidden" name="nama_tampilan"  id="hidNama">
    <div id="hidMetadataIds"></div>
    <div id="hidLocationIds"></div>
    <div id="hidUrutanBy"></div>
</form>

<script>
// ═══════════════════════════════════════════════════════════════
// DATA LOKASI — di-embed dari server
// ═══════════════════════════════════════════════════════════════
const ALL_LOCATIONS = @json(
    \App\Models\Location::select('location_id', 'nama_wilayah')
        ->orderBy('nama_wilayah')
        ->get()
        ->map(fn($l) => ['id' => (string)$l->location_id, 'nama' => $l->nama_wilayah])
        ->values()
);

const LOC_PROVINSI = ALL_LOCATIONS.filter(l => l.id.slice(-8) === '00000000');

const idxKab = {};
const idxKec = {};
const idxDes = {};

ALL_LOCATIONS.forEach(l => {
    if (l.id.length < 10) return;
    const isProvinsi  = l.id.slice(-8) === '00000000';
    const isKabupaten = !isProvinsi && l.id.slice(-6) === '000000';
    const isKecamatan = !isProvinsi && !isKabupaten && l.id.slice(-4) === '0000';
    const isDesa      = !isProvinsi && !isKabupaten && !isKecamatan;
    if (isKabupaten) { const p = l.id.slice(0,2); if (!idxKab[p]) idxKab[p]=[]; idxKab[p].push(l); }
    else if (isKecamatan) { const p = l.id.slice(0,4); if (!idxKec[p]) idxKec[p]=[]; idxKec[p].push(l); }
    else if (isDesa) { const p = l.id.slice(0,6); if (!idxDes[p]) idxDes[p]=[]; idxDes[p].push(l); }
});

// ═══════════════════════════════════════════════════════════════
// ENDPOINTS & CONFIG
// ═══════════════════════════════════════════════════════════════
const SEARCH_META_URL   = '{{ route("template.search_metadata") }}';
const FETCH_PREVIEW_URL = '{{ route("template.fetch_preview") }}';
const URL_CHILDREN      = '{{ route("template.child_locations") }}';
const CSRF              = '{{ csrf_token() }}';
const IS_LOGGED_IN      = {{ Auth::check() ? 'true' : 'false' }};

// ═══════════════════════════════════════════════════════════════
// STATE CASCADE WILAYAH (native <select> — prefix m_)
// ═══════════════════════════════════════════════════════════════
const M_LEVELS = ['provinsi', 'kabupaten', 'kecamatan', 'desa'];
const M_LEVEL_LABEL = {
    provinsi: 'Provinsi', kabupaten: 'Kabupaten/Kota',
    kecamatan: 'Kecamatan', desa: 'Desa/Kelurahan',
};
const M_URLS = {
    kabupaten: '{{ route("template.get_kabupaten") }}',
    kecamatan: '{{ route("template.get_kecamatan_wil") }}',
    desa     : '{{ route("template.get_desa_wil") }}',
};
const mSelLoc = { provinsi: null, kabupaten: null, kecamatan: null, desa: null };
const mCaches = { kabupaten: {}, kecamatan: {}, desa: {} };

function mCap(s) { return s.charAt(0).toUpperCase() + s.slice(1); }

async function mSelectFromNative(level, id, nama) {
    if (!id) { mClearFromLevel(level); return; }
    mSelLoc[level] = { id, nama };
    document.getElementById('m_val' + mCap(level)).value = id;
    const idx = M_LEVELS.indexOf(level);
    M_LEVELS.slice(idx + 1).forEach(l => mResetLevel(l));
    const next = M_LEVELS[idx + 1];
    if (next && M_URLS[next]) await mFillSelect(next, id);
    mUpdateBadge();
}

async function mFillSelect(level, parentId) {
    const sel = document.getElementById('m_select' + mCap(level));
    if (mCaches[level][parentId]) { mRenderOptions(sel, level, mCaches[level][parentId]); return; }
    sel.disabled = true;
    sel.classList.add('opacity-60');
    const paramKey = level === 'kabupaten' ? 'provinsi_id' : level === 'kecamatan' ? 'kabupaten_id' : 'kecamatan_id';
    try {
        const r = await fetch(`${M_URLS[level]}?${paramKey}=${parentId}`);
        const d = await r.json();
        mCaches[level][parentId] = d;
        mRenderOptions(sel, level, d);
    } catch { sel.innerHTML = '<option value="">Gagal memuat</option>'; }
    finally {
        sel.disabled = false;
        sel.classList.remove('opacity-60', 'bg-gray-100', 'text-gray-400', 'cursor-not-allowed');
        sel.classList.add('bg-white', 'text-gray-700');
    }
}

function mRenderOptions(sel, level, items) {
    sel.innerHTML = `<option value="">— Pilih ${M_LEVEL_LABEL[level]} —</option>` +
        items.map(x => `<option value="${x.location_id}">${escH(x.nama_wilayah)}</option>`).join('');
}

function mResetLevel(level) {
    mSelLoc[level] = null;
    document.getElementById('m_val' + mCap(level)).value = '';
    const sel = document.getElementById('m_select' + mCap(level));
    sel.innerHTML = `<option value="">— Pilih ${M_LEVEL_LABEL[M_LEVELS[M_LEVELS.indexOf(level)-1]]} dulu —</option>`;
    sel.disabled = true;
    sel.classList.add('bg-gray-100', 'text-gray-400', 'cursor-not-allowed');
    sel.classList.remove('bg-white', 'text-gray-700');
}

function mClearFromLevel(level) {
    const idx = M_LEVELS.indexOf(level);
    M_LEVELS.slice(idx).forEach(l => {
        mSelLoc[l] = null;
        document.getElementById('m_val' + mCap(l)).value = '';
        if (l !== 'provinsi') {
            const sel = document.getElementById('m_select' + mCap(l));
            sel.innerHTML = `<option value="">— Pilih ${M_LEVEL_LABEL[M_LEVELS[M_LEVELS.indexOf(l)-1]]} dulu —</option>`;
            sel.disabled = true;
            sel.classList.add('bg-gray-100', 'text-gray-400', 'cursor-not-allowed');
            sel.classList.remove('bg-white', 'text-gray-700');
        } else {
            document.getElementById('m_selectProvinsi').value = '';
        }
    });
    mUpdateBadge();
}

function mUpdateBadge() {
    let deepest = null;
    for (let i = M_LEVELS.length - 1; i >= 0; i--) {
        if (mSelLoc[M_LEVELS[i]]) { deepest = { level: M_LEVELS[i], ...mSelLoc[M_LEVELS[i]] }; break; }
    }
    const badge = document.getElementById('m_selectedWilayahBadge');
    if (deepest) {
        badge.classList.remove('hidden');
        document.getElementById('m_badgeNama').textContent  = deepest.nama;
        document.getElementById('m_badgeLevel').textContent = '(' + M_LEVEL_LABEL[deepest.level] + ')';
    } else {
        badge.classList.add('hidden');
    }
}

function mGetDeepestLocId() {
    for (let i = M_LEVELS.length - 1; i >= 0; i--) {
        if (mSelLoc[M_LEVELS[i]]) return mSelLoc[M_LEVELS[i]].id;
    }
    return null;
}

// ═══════════════════════════════════════════════════════════════
// METADATA DROPDOWN — pre-load saat DOMContentLoaded
// ═══════════════════════════════════════════════════════════════
let selectedMeta = {};
let metaCache    = null;
let metaTimeout  = null;

document.addEventListener('DOMContentLoaded', () => {
    fetch(`${SEARCH_META_URL}?q=`)
        .then(r => r.json())
        .then(d => { metaCache = d; })
        .catch(() => {});
});

function onMetaSearchFocus() {
    const box = document.getElementById('metaDropList');
    if (!box.classList.contains('hidden')) return;
    if (metaCache) { renderMetaDrop(metaCache); return; }
    fetchMetaDrop('');
}

function onMetaSearchInput() {
    clearTimeout(metaTimeout);
    const q = document.getElementById('metaSearch').value.trim();
    metaTimeout = setTimeout(() => {
        if (metaCache) {
            const filtered = q
                ? metaCache.filter(m => m.nama.toLowerCase().includes(q.toLowerCase()))
                : metaCache;
            renderMetaDrop(filtered);
        } else {
            fetchMetaDrop(q);
        }
    }, 150);
}

function fetchMetaDrop(q) {
    const box = document.getElementById('metaDropList');
    box.classList.remove('hidden');
    fetch(`${SEARCH_META_URL}?q=${encodeURIComponent(q)}`)
        .then(r => r.json())
        .then(d => { if (!q) metaCache = d; renderMetaDrop(d); })
        .catch(() => {
            box.innerHTML = '<p class="px-4 py-3 text-xs text-red-400 text-center">Gagal memuat</p>';
        });
}

function renderMetaDrop(results) {
    const box = document.getElementById('metaDropList');
    if (!results.length) {
        box.innerHTML = '<p class="px-4 py-3 text-xs text-gray-400 text-center">Tidak ada hasil</p>';
        box.classList.remove('hidden');
        return;
    }
    box.innerHTML = results.map(m => {
        const sel = !!selectedMeta[m.metadata_id];
        return `<button type="button"
            onclick="toggleMeta(${m.metadata_id},'${escH(m.nama)}','${escH(m.klasifikasi||'')}','${escH(m.satuan_data||'')}','${escH(m.frekuensi_penerbitan||'')}')"
            class="w-full text-left px-4 py-2.5 flex items-start gap-2.5 border-b border-gray-50 last:border-0 transition-colors
                   ${sel ? 'bg-sky-50' : 'hover:bg-gray-50'}">
            <span class="mt-0.5 shrink-0">
                ${sel ? '<i class="fas fa-check-circle text-sky-500 text-xs"></i>' : '<i class="far fa-circle text-gray-300 text-xs"></i>'}
            </span>
            <span class="flex flex-col gap-0.5 min-w-0">
                <span class="font-medium text-gray-800 text-xs">${escH(m.nama)}</span>
                <span class="text-gray-400 text-xs truncate">
                    ${escH(m.klasifikasi||'')}${m.satuan_data ? ' · ' + escH(m.satuan_data) : ''}${m.frekuensi_penerbitan ? ' · ' + escH(m.frekuensi_penerbitan) : ''}
                </span>
            </span>
        </button>`;
    }).join('');
    box.classList.remove('hidden');
}

function toggleMeta(id, nama, klasifikasi, satuan, frekuensi) {
    if (selectedMeta[id]) { delete selectedMeta[id]; }
    else { selectedMeta[id] = { metadata_id: id, nama, klasifikasi, satuan_data: satuan, frekuensi_penerbitan: frekuensi }; }
    renderMetaChips();
    const q = document.getElementById('metaSearch').value.trim();
    const list = metaCache ? (q ? metaCache.filter(m => m.nama.toLowerCase().includes(q.toLowerCase())) : metaCache) : null;
    if (list) renderMetaDrop(list);
}

function renderMetaChips() {
    document.getElementById('selectedMetaChips').innerHTML =
        Object.values(selectedMeta).map(m =>
            `<span class="inline-flex items-center gap-1 bg-sky-50 border border-sky-200 text-sky-700 text-xs px-2.5 py-1 rounded-full">
                <i class="fas fa-database text-sky-300 text-xs"></i>
                ${escH(m.nama)}
                <button type="button" onclick="removeMeta(${m.metadata_id})"
                        class="text-sky-400 hover:text-sky-600 ml-1 leading-none">×</button>
            </span>`
        ).join('');
}

function removeMeta(id) { delete selectedMeta[id]; renderMetaChips(); }

// ═══════════════════════════════════════════════════════════════
// LOAD PREVIEW — fetch dari server
// ═══════════════════════════════════════════════════════════════
let allGrouped  = {};
let activeTab   = '';
let sortedRows  = [];
let selectedMap = {};
let expandedMap = {};
const PAGE_SIZE = 15;
let currentPage = 1;

async function loadMetadataPreview() {
    const metaIds = Object.keys(selectedMeta);
    if (!metaIds.length) { alert('Pilih minimal 1 metadata terlebih dahulu.'); return; }

    const locId = mGetDeepestLocId();
    const body  = new URLSearchParams();
    body.append('_token', CSRF);
    metaIds.forEach(id => body.append('metadata_ids[]', id));
    if (locId) body.append('location_ids[]', locId);

    const btn = document.querySelector('button[onclick="loadMetadataPreview()"]');
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-circle-notch fa-spin mr-2"></i> Memuat...'; }

    try {
        const r = await fetch(FETCH_PREVIEW_URL, { method: 'POST', body });
        const d = await r.json();
        if (!d.success) throw new Error('Gagal memuat preview.');

        allGrouped = flattenGrouped(d.grouped || {});
        selectedMap = {};
        expandedMap = {};
        currentPage = 1;

        activateTabs(allGrouped);
        document.getElementById('sectionResult').classList.remove('hidden');

        const metaNames = Object.values(selectedMeta).map(m => m.nama).join(', ');
        document.getElementById('resultDesc').textContent =
            (metaNames.length > 60 ? metaNames.slice(0, 60) + '…' : metaNames) +
            (locId ? ' · ' + (mSelLoc.desa||mSelLoc.kecamatan||mSelLoc.kabupaten||mSelLoc.provinsi)?.nama : '');
    } catch (e) {
        alert(e.message);
    } finally {
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-search mr-2"></i> Pilih &amp; Tampilkan'; }
    }
}

// ═══════════════════════════════════════════════════════════════
// TAB FREKUENSI
// ═══════════════════════════════════════════════════════════════
const FREQ_KEYS = ['dekade', 'tahunan', 'semester', 'kuartal', 'bulanan'];

function activateTabs(grouped) {
    let firstActive = '';
    FREQ_KEYS.forEach(freq => {
        const count  = (grouped[freq] || []).length;
        const tabBtn = document.getElementById('tab-' + freq);
        const tabCnt = document.getElementById('tab-count-' + freq);
        tabCnt.textContent = count;
        if (count > 0) {
            tabBtn.disabled = false;
            tabBtn.classList.remove('cursor-not-allowed', 'text-gray-400');
            tabBtn.classList.add('cursor-pointer', 'text-gray-600', 'hover:text-gray-800');
            tabCnt.classList.remove('bg-gray-100', 'text-gray-400');
            tabCnt.classList.add('bg-sky-100', 'text-sky-600');
            if (!firstActive) firstActive = freq;
        } else {
            tabBtn.disabled = true;
            tabBtn.classList.add('cursor-not-allowed', 'text-gray-400');
            tabBtn.classList.remove('cursor-pointer', 'text-gray-600', 'hover:text-gray-800');
            tabCnt.classList.add('bg-gray-100', 'text-gray-400');
            tabCnt.classList.remove('bg-sky-100', 'text-sky-600');
        }
    });
    const total = FREQ_KEYS.reduce((s, f) => s + (grouped[f] || []).length, 0);
    document.getElementById('totalBadge').textContent = total + ' baris';
    if (firstActive) switchTab(firstActive);
}

function switchTab(freq) {
    activeTab = freq;
    FREQ_KEYS.forEach(f => {
        const btn = document.getElementById('tab-' + f);
        if (f === freq) {
            btn.classList.add('border-sky-500', 'text-sky-600');
            btn.classList.remove('border-transparent', 'text-gray-600');
        } else {
            btn.classList.remove('border-sky-500', 'text-sky-600');
            if (!btn.disabled) btn.classList.add('border-transparent', 'text-gray-600');
        }
    });

    const pf = document.getElementById('periodeFilter');
    const sd = document.getElementById('periodeSimple');
    const cd = document.getElementById('periodeComplex');
    const pl = document.getElementById('periodeLabel');
    const sl = document.getElementById('periodeSimpleLabel');
    pf.classList.remove('hidden');

    if (freq === 'dekade') {
        sd.classList.remove('hidden'); cd.classList.add('hidden');
        if (sl) sl.textContent = 'Rentang Dekade:';
        document.getElementById('periodFromSimple').placeholder = 'cth: 2010';
        document.getElementById('periodToSimple').placeholder   = 'cth: 2020';
    } else if (freq === 'tahunan') {
        sd.classList.remove('hidden'); cd.classList.add('hidden');
        if (sl) sl.textContent = 'Rentang Tahun:';
        document.getElementById('periodFromSimple').placeholder = 'Dari tahun';
        document.getElementById('periodToSimple').placeholder   = 'Sampai tahun';
    } else {
        sd.classList.add('hidden'); cd.classList.remove('hidden');
        if (pl) pl.textContent = freq === 'semester' ? 'Semester (1-2):' : (freq === 'kuartal' ? 'Kuartal (1-4):' : 'Bulan (1-12):');
        const maxVal = freq === 'semester' ? 2 : (freq === 'kuartal' ? 4 : 12);
        const pFrom = document.getElementById('periodFrom');
        const pTo   = document.getElementById('periodTo');
        if (pFrom) pFrom.max = maxVal;
        if (pTo)   pTo.max   = maxVal;
    }

    sortedRows  = [...(allGrouped[freq] || [])];
    expandedMap = {};
    currentPage = 1;
    renderTable();
}

// ═══════════════════════════════════════════════════════════════
// FILTER PERIODE
// ═══════════════════════════════════════════════════════════════
function buildFilterBody() {
    const body = new URLSearchParams();
    body.append('_token', CSRF);
    Object.keys(selectedMeta).forEach(id => body.append('metadata_ids[]', id));
    const locId = mGetDeepestLocId();
    if (locId) body.append('location_ids[]', locId);
    if (activeTab) {
        body.append('frekuensi', activeTab);
        if (['dekade', 'tahunan'].includes(activeTab)) {
            const from = document.getElementById('periodFromSimple')?.value;
            const to   = document.getElementById('periodToSimple')?.value;
            if (from) body.append('period_from', from);
            if (to)   body.append('period_to',   to);
        } else {
            const yf = document.getElementById('yearFrom')?.value;
            const yt = document.getElementById('yearTo')?.value;
            const pf = document.getElementById('periodFrom')?.value;
            const pt = document.getElementById('periodTo')?.value;
            if (yf) body.append('year_from',   yf);
            if (yt) body.append('year_to',     yt);
            if (pf) body.append('period_from', pf);
            if (pt) body.append('period_to',   pt);
        }
    }
    return body;
}

async function applyPeriodeFilter() {
    if (!Object.keys(selectedMeta).length || !activeTab) return;
    const applyBtn = document.querySelector('button[onclick="applyPeriodeFilter()"]');
    if (applyBtn) { applyBtn.disabled = true; applyBtn.innerHTML = '<i class="fas fa-circle-notch fa-spin mr-1"></i> Memuat...'; }
    try {
        const r = await fetch(FETCH_PREVIEW_URL, { method: 'POST', body: buildFilterBody() });
        const d = await r.json();
        if (!d.success) throw new Error('Server error');
        allGrouped = flattenGrouped(d.grouped || {});
        expandedMap = {};
        currentPage = 1;
        FREQ_KEYS.forEach(freq => {
            const cnt = document.getElementById('tab-count-' + freq);
            if (cnt) cnt.textContent = (allGrouped[freq] || []).length;
        });
        sortedRows = [...(allGrouped[activeTab] || [])];
        renderTable();
        const total = FREQ_KEYS.reduce((s, f) => s + (allGrouped[f] || []).length, 0);
        document.getElementById('totalBadge').textContent = total + ' baris';
    } catch(e) { alert('Gagal memuat: ' + e.message); }
    finally {
        if (applyBtn) { applyBtn.disabled = false; applyBtn.innerHTML = '<i class="fas fa-search"></i> Tampilkan'; }
    }
}

function resetPeriodeFilter() {
    ['periodFromSimple','periodToSimple','yearFrom','yearTo','periodFrom','periodTo']
        .forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
    loadMetadataPreview();
}


function flattenGrouped(grouped) {
    const result = {};
    Object.keys(grouped).forEach(freq => {
        result[freq] = [];
        (grouped[freq] || []).forEach(m => {
            const locs = (m.locations && m.locations.length)
                ? m.locations
                : [{ location_id: 0, nama_wilayah: 'Semua Wilayah', has_children: false }];
            locs.forEach(loc => {
                result[freq].push({
                    metadata_id:          m.metadata_id,
                    nama:                 m.nama,
                    klasifikasi:          m.klasifikasi || '',
                    satuan_data:          m.satuan_data || '',
                    frekuensi_penerbitan: m.frekuensi_penerbitan || '',
                    location_id:          loc.location_id,
                    nama_wilayah:         loc.nama_wilayah,
                    has_children:         loc.has_children,
                    depth:                0,
                });
            });
        });
    });
    return result;
}

// ═══════════════════════════════════════════════════════════════
// RENDER TABEL
// ═══════════════════════════════════════════════════════════════
function rowKey(row) { return `${row.metadata_id}_${row.location_id}_${row.depth || 0}`; }

function buildFlat(baseRows) {
    const result = [];
    function addRows(rows) {
        rows.forEach(row => {
            result.push(row);
            const key = rowKey(row);
            if (expandedMap[key]) addRows(expandedMap[key]);
        });
    }
    addRows(baseRows);
    return result;
}

function renderTable() {
    const tbody = document.getElementById('resultTbody');
    const flat  = buildFlat(sortedRows);
    const total = flat.length;

    if (!total) {
        tbody.innerHTML = `<tr><td colspan="4" class="px-4 py-10 text-center text-gray-400 text-sm">
            <i class="fas fa-inbox text-3xl text-gray-200 block mb-2"></i>
            Tidak ada metadata untuk filter ini.
        </td></tr>`;
        document.getElementById('paginWrap').classList.add('hidden');
        return;
    }

    const start = (currentPage - 1) * PAGE_SIZE;
    const end   = Math.min(start + PAGE_SIZE, total);
    tbody.innerHTML = flat.slice(start, end).map(row => buildRow(row)).join('');

    const totalPages = Math.ceil(total / PAGE_SIZE);
    const pw = document.getElementById('paginWrap');
    if (totalPages > 1) {
        pw.classList.remove('hidden');
        document.getElementById('paginInfo').textContent = `Menampilkan ${start+1}–${end} dari ${total}`;
        let btns = '';
        for (let p = 1; p <= totalPages; p++) {
            btns += `<button onclick="goPage(${p})"
                class="w-7 h-7 text-xs rounded-md font-medium transition-colors
                       ${p === currentPage ? 'bg-sky-500 text-white' : 'border border-gray-200 text-gray-500 hover:bg-gray-50'}">${p}</button>`;
        }
        document.getElementById('paginBtns').innerHTML = btns;
    } else {
        pw.classList.add('hidden');
    }
}

function buildRow(row) {
    const key        = rowKey(row);
    const depth      = row.depth || 0;
    const checked    = !!selectedMap[key];
    const indent     = depth * 24;
    const isExpanded = !!expandedMap[key];

    const displayName = `<span class="font-${depth === 0 ? 'semibold' : 'medium'} text-gray-800">${escH(row.nama)} di ${escH(row.nama_wilayah)}</span>`;

    let detailBtn = `<span class="text-gray-300 text-xs">—</span>`;
    if (row.has_children) {
        detailBtn = `<button type="button"
            onclick="toggleExpand('${key}')"
            title="${isExpanded ? 'Sembunyikan turunan' : 'Tampilkan 1 level wilayah di bawah'}"
            class="inline-flex items-center justify-center w-7 h-7 rounded border font-bold text-xs select-none transition-colors
                   ${isExpanded
                       ? 'bg-sky-100 border-sky-300 text-sky-700 hover:bg-sky-200'
                       : 'bg-white border-gray-300 text-gray-600 hover:border-sky-400 hover:text-sky-600'}">
            ${isExpanded ? '<i class="fa-solid fa-angle-left"></i>' : '<i class="fa-solid fa-angle-down"></i>'}
        </button>`;
    }

    const rowBg = depth === 0
        ? 'hover:bg-sky-50'
        : (depth === 1 ? 'bg-sky-50/50 hover:bg-sky-100/60' : 'bg-violet-50/40 hover:bg-violet-100/50');
    const borderStyle = depth > 0
        ? `border-left: 3px solid ${depth === 1 ? '#7dd3fc' : '#c4b5fd'};` : '';

    return `<tr class="${rowBg} transition-colors">
        <td class="py-3 pr-2" style="padding-left:${12 + indent}px; ${borderStyle}">
            <input type="checkbox" class="row-chk rounded border-gray-300 cursor-pointer"
                value="${escH(key)}"
                onchange="onRowCheck(this, ${row.metadata_id}, ${row.location_id}, ${depth})"
                ${checked ? 'checked' : ''}>
        </td>
        <td class="px-3 py-3 text-xs" style="${depth > 0 ? 'padding-left:' + (12 + indent) + 'px' : 'padding-left:16px'}">
            ${depth > 0 ? '<span class="text-gray-400 mr-1.5">↳</span>' : ''}
            ${displayName}
            ${row.frekuensi_penerbitan ? `<span class="ml-1.5 text-gray-400 font-normal">(${escH(row.frekuensi_penerbitan)})</span>` : ''}
        </td>
        <td class="px-4 py-3 text-center">${detailBtn}</td>
        <td class="px-4 py-3 text-center">
            <button type="button"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 border border-gray-200
                       hover:border-sky-300 hover:bg-sky-50 text-gray-500 hover:text-sky-600
                       text-xs font-medium rounded-lg transition-colors">
                <i class="fas fa-chart-bar text-xs"></i> Grafik
            </button>
        </td>
    </tr>`;
}

// ═══════════════════════════════════════════════════════════════
// EXPAND / COLLAPSE
// ═══════════════════════════════════════════════════════════════
async function toggleExpand(key) {
    if (expandedMap[key]) { collapseKey(key); renderTable(); return; }

    const parts      = key.split('_');
    const metadataId = parseInt(parts[0]);
    const locationId = parseInt(parts[1]);
    const depth      = parseInt(parts[2] || '0');
    const parentRow  = findRowByKey(key);

    try {
        const r = await fetch(`${URL_CHILDREN}?metadata_id=${metadataId}&location_id=${locationId}`);
        const d = await r.json();
        if (!d.children || !d.children.length) {
            if (parentRow) parentRow.has_children = false;
            renderTable(); return;
        }
        expandedMap[key] = d.children.map(c => ({
            metadata_id:          metadataId,
            nama:                 parentRow ? parentRow.nama : '',
            klasifikasi:          parentRow ? parentRow.klasifikasi : '',
            satuan_data:          parentRow ? parentRow.satuan_data : '',
            frekuensi_penerbitan: parentRow ? parentRow.frekuensi_penerbitan : '',
            location_id:          c.location_id,
            nama_wilayah:         c.nama_wilayah,
            has_children:         c.has_children,
            depth:                depth + 1,
        }));
        renderTable();
    } catch(e) { console.error('Expand error:', e); }
}

function collapseKey(key) {
    if (expandedMap[key]) {
        expandedMap[key].forEach(child => collapseKey(rowKey(child)));
        delete expandedMap[key];
    }
}

function findRowByKey(key) {
    for (const row of sortedRows) { if (rowKey(row) === key) return row; }
    for (const children of Object.values(expandedMap)) {
        for (const child of children) { if (rowKey(child) === key) return child; }
    }
    return null;
}

// ═══════════════════════════════════════════════════════════════
// SELECTION
// ═══════════════════════════════════════════════════════════════
function onRowCheck(cb, metadataId, locationId, depth) {
    const key = cb.value;
    if (cb.checked) {
        const flat = buildFlat(sortedRows);
        const row  = flat.find(r => rowKey(r) === key);
        if (row) selectedMap[key] = row;
    } else { delete selectedMap[key]; }
    updateSelBar();
}

function toggleAll(masterCb) {
    document.querySelectorAll('.row-chk').forEach(cb => {
        cb.checked = masterCb.checked;
        const parts = cb.value.split('_');
        onRowCheck(cb, parseInt(parts[0]), parseInt(parts[1]), parseInt(parts[2] || '0'));
    });
}

function clearAllSel() {
    selectedMap = {};
    document.querySelectorAll('.row-chk').forEach(cb => cb.checked = false);
    const ca = document.getElementById('checkAll'); if (ca) ca.checked = false;
    updateSelBar();
}

function updateSelBar() {
    const count = Object.keys(selectedMap).length;
    const bar = document.getElementById('selBar');
    if (count > 0) { bar.classList.remove('hidden'); bar.style.display = 'flex'; }
    else { bar.classList.add('hidden'); bar.style.display = 'none'; }
    document.getElementById('selCount').textContent = count + ' metadata dipilih';
}

// ═══════════════════════════════════════════════════════════════
// PENGATURAN URUTAN
// ═══════════════════════════════════════════════════════════════
function terapkanUrutan() {
    const byKlas = document.getElementById('chkKlasifikasi').checked;
    const byWil  = document.getElementById('chkWilayah').checked;
    const base   = allGrouped[activeTab] || [];
    if (!byKlas && !byWil) {
        sortedRows = [...base];
    } else {
        sortedRows = [...base].sort((a, b) => {
            if (byKlas) { const k = (a.klasifikasi||'').localeCompare(b.klasifikasi||''); if (k !== 0) return k; }
            if (byWil)  { const w = (a.nama_wilayah||'').localeCompare(b.nama_wilayah||''); if (w !== 0) return w; }
            return (a.nama||'').localeCompare(b.nama||'');
        });
    }
    expandedMap = {};
    currentPage = 1;
    renderTable();
}

// ═══════════════════════════════════════════════════════════════
// PAGINATION
// ═══════════════════════════════════════════════════════════════
function goPage(p) { currentPage = p; renderTable(); }

// ═══════════════════════════════════════════════════════════════
// MODAL SIMPAN
// ═══════════════════════════════════════════════════════════════
function openSaveModal() {
    const count = Object.keys(selectedMap).length;
    if (!count) { alert('Pilih minimal 1 metadata untuk disimpan.'); return; }
    document.getElementById('modalMetaCount').textContent = count;
    document.getElementById('inputNama').value = '';
    document.getElementById('errNama').classList.add('hidden');
    document.getElementById('modalSave').style.display = 'flex';
    setTimeout(() => document.getElementById('inputNama').focus(), 100);
}

function closeModal() { document.getElementById('modalSave').style.display = 'none'; }

async function submitTemplate() {
    const nama = document.getElementById('inputNama').value.trim();
    if (!nama) { document.getElementById('errNama').classList.remove('hidden'); return; }

    const metaSet = new Set(Object.values(selectedMap).map(r => r.metadata_id));
    const locSet  = new Set(Object.values(selectedMap).map(r => r.location_id));

    if (IS_LOGGED_IN) {
        document.getElementById('hidNama').value = nama;
        document.getElementById('hidMetadataIds').innerHTML =
            [...metaSet].map(id => `<input type="hidden" name="metadata_ids[]" value="${id}">`).join('');
        document.getElementById('hidLocationIds').innerHTML =
            [...locSet].map(id => `<input type="hidden" name="location_ids[]" value="${id}">`).join('');
        document.getElementById('hidUrutanBy').innerHTML =
            [...document.querySelectorAll('input[name="urutan_by[]"]:checked')]
                .map(c => `<input type="hidden" name="urutan_by[]" value="${c.value}">`).join('');
        document.getElementById('formSaveTemplateHidden').submit();
    } else {
        const body = new URLSearchParams();
        body.append('_token', CSRF);
        body.append('nama_tampilan', nama);
        body.append('jenis_template', 'metadata');
        [...metaSet].forEach(id => body.append('metadata_ids[]', id));
        [...locSet].forEach(id  => body.append('location_ids[]', id));
        try {
            const r = await fetch('{{ route("template.store") }}', { method: 'POST', body });
            const d = await r.json();
            if (d.success && d.storage === 'local') {
                const existing = JSON.parse(localStorage.getItem('savedTemplates') || '[]');
                d.template_data.local_id = 'tmpl_' + Date.now();
                existing.push(d.template_data);
                localStorage.setItem('savedTemplates', JSON.stringify(existing));
                alert(`Template "${nama}" disimpan di browser.\n(Login untuk menyimpan ke server)`);
                closeModal();
                window.location.href = d.redirect;
            }
        } catch(e) { alert('Gagal menyimpan: ' + e.message); }
    }
}

// ═══════════════════════════════════════════════════════════════
// UTILS
// ═══════════════════════════════════════════════════════════════
function escH(s) {
    const d = document.createElement('div');
    d.innerText = s || '';
    return d.innerHTML;
}

document.addEventListener('click', e => {
    if (!document.getElementById('metaDropWrap').contains(e.target))
        document.getElementById('metaDropList').classList.add('hidden');
    if (e.target === document.getElementById('modalSave')) closeModal();
});
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });
</script>
@endsection