@extends('layouts.main')

@section('content')

{{-- ── Aksen warna untuk partial _preview-table (violet/ungu) ── --}}
<style>
    .preview-badge        { background:#f5f3ff; color:#7c3aed; border-color:#ddd6fe; }
    .preview-btn-primary  { background:#8b5cf6; }
    .preview-btn-primary:hover { background:#7c3aed; }
    #accentBar            { background:#8b5cf6; }
    .preview-modal-header { background: linear-gradient(135deg, #8b5cf6, #6d28d9); }
    .preview-summary-box  { background:#f5f3ff; border-color:#ddd6fe; }
    .preview-summary-icon { background:#ede9fe; color:#7c3aed; }
    .preview-summary-text { color:#6d28d9; }
    .preview-summary-sub  { color:#7c3aed; }
    #selectionBarPreview  { background:#f5f3ff; border-color:#ddd6fe; }
    #selectionBarPreview p { color:#6d28d9; }
    #selectionBarPreview button { color:#7c3aed; }
</style>

<div class="mt-2 bg-white rounded-xl shadow p-6">

    {{-- BREADCRUMB --}}
    <div class="flex items-center gap-2 text-sm text-gray-400 mb-5">
        <a href="{{ route('data.index') }}" class="hover:text-sky-500 transition-colors">Data</a>
        <i class="fas fa-chevron-right text-xs"></i>
        <a href="{{ route('template.create') }}" class="hover:text-sky-500 transition-colors">Buat Template</a>
        <i class="fas fa-chevron-right text-xs"></i>
        <span class="text-gray-600 font-medium">Template Klasifikasi</span>
    </div>

    <div class="mb-6 flex items-start justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-800">Buat Template Klasifikasi</h1>
            <p class="text-sm text-gray-400 mt-1">Pilih klasifikasi data dan wilayah yang ingin ditampilkan</p>
        </div>
        <span class="px-3 py-1.5 bg-violet-50 text-violet-600 border border-violet-100 text-xs font-semibold rounded-full">
            <i class="fas fa-tags mr-1"></i> Jenis: Klasifikasi
        </span>
    </div>

    {{-- ═══ SECTION 1 ═══ --}}
    <div class="p-5 border border-gray-200 rounded-xl mb-2">
        <h2 class="text-sm font-bold text-gray-700 mb-4">
            <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-violet-500 text-white text-xs font-bold mr-2">1</span>
            Pilih Klasifikasi &amp; Wilayah
        </h2>

        {{-- KLASIFIKASI --}}
        <div class="mb-4">
            <label class="block text-xs text-gray-500 font-medium mb-1">
                <i class="fas fa-tags mr-1 text-gray-400"></i> Klasifikasi
                <span class="text-red-500">*</span>
            </label>
            <select id="klasifikasiSelect"
                    onchange="onKlasifikasiChange()"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm
                           focus:outline-none focus:ring-2 focus:ring-violet-400 bg-white">
                <option value="">— Pilih Klasifikasi —</option>
                @foreach($klasifikasiList as $k)
                    <option value="{{ $k }}">{{ $k }}</option>
                @endforeach
            </select>
        </div>

        {{-- WILAYAH cascade — native <select>, tanpa spinner/loading --}}
        <div class="mb-4">
            <label class="block text-xs text-gray-500 font-medium mb-2">
                <i class="fas fa-map-marker-alt mr-1 text-gray-400"></i> Wilayah
                <span class="text-gray-400 font-normal">(opsional — pilih hingga level yang diinginkan)</span>
            </label>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">

                {{-- PROVINSI --}}
                <div>
                    <label class="block text-xs text-gray-500 font-semibold mb-1.5 uppercase tracking-wide">Provinsi</label>
                    <div class="relative" id="k_wrapProvinsi">
                        <select id="k_selectProvinsi"
                                onchange="kSelectFromNative('provinsi', this.value, this.options[this.selectedIndex].text)"
                                class="w-full border border-gray-300 rounded-lg pl-7 pr-3 py-2 text-sm
                                       focus:outline-none focus:ring-2 focus:ring-violet-400 bg-white appearance-none">
                            <option value="">— Pilih Provinsi —</option>
                            @foreach($provinsiList as $p)
                                <option value="{{ $p->location_id }}">{{ $p->nama_wilayah }}</option>
                            @endforeach
                        </select>
                        <i class="fas fa-map-marker-alt absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                    </div>
                    <input type="hidden" id="k_valProvinsi">
                </div>

                {{-- KABUPATEN --}}
                <div>
                    <label class="block text-xs text-gray-500 font-semibold mb-1.5 uppercase tracking-wide">Kabupaten / Kota</label>
                    <div class="relative" id="k_wrapKabupaten">
                        <select id="k_selectKabupaten" disabled
                                onchange="kSelectFromNative('kabupaten', this.value, this.options[this.selectedIndex].text)"
                                class="w-full border border-gray-200 rounded-lg pl-7 pr-3 py-2 text-sm
                                       focus:outline-none focus:ring-2 focus:ring-violet-400 bg-gray-100 text-gray-400 cursor-not-allowed appearance-none">
                            <option value="">— Pilih Provinsi dulu —</option>
                        </select>
                        <i class="fas fa-map-marker-alt absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                    </div>
                    <input type="hidden" id="k_valKabupaten">
                </div>

                {{-- KECAMATAN --}}
                <div>
                    <label class="block text-xs text-gray-500 font-semibold mb-1.5 uppercase tracking-wide">Kecamatan</label>
                    <div class="relative" id="k_wrapKecamatan">
                        <select id="k_selectKecamatan" disabled
                                onchange="kSelectFromNative('kecamatan', this.value, this.options[this.selectedIndex].text)"
                                class="w-full border border-gray-200 rounded-lg pl-7 pr-3 py-2 text-sm
                                       focus:outline-none focus:ring-2 focus:ring-violet-400 bg-gray-100 text-gray-400 cursor-not-allowed appearance-none">
                            <option value="">— Pilih Kabupaten dulu —</option>
                        </select>
                        <i class="fas fa-map-marker-alt absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                    </div>
                    <input type="hidden" id="k_valKecamatan">
                </div>

                {{-- DESA --}}
                <div>
                    <label class="block text-xs text-gray-500 font-semibold mb-1.5 uppercase tracking-wide">Desa / Kelurahan</label>
                    <div class="relative" id="k_wrapDesa">
                        <select id="k_selectDesa" disabled
                                onchange="kSelectFromNative('desa', this.value, this.options[this.selectedIndex].text)"
                                class="w-full border border-gray-200 rounded-lg pl-7 pr-3 py-2 text-sm
                                       focus:outline-none focus:ring-2 focus:ring-violet-400 bg-gray-100 text-gray-400 cursor-not-allowed appearance-none">
                            <option value="">— Pilih Kecamatan dulu —</option>
                        </select>
                        <i class="fas fa-map-marker-alt absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                    </div>
                    <input type="hidden" id="k_valDesa">
                </div>
            </div>

            {{-- Badge wilayah terpilih --}}
            <div id="k_selectedWilayahBadge"
                 class="hidden mt-3 flex items-center gap-2.5 p-2.5 bg-violet-50 border border-violet-200 rounded-lg w-fit">
                <i class="fas fa-map-marker-alt text-violet-500 text-xs"></i>
                <span class="text-xs text-violet-700 font-semibold" id="k_badgeNama">—</span>
                <span class="text-xs text-violet-500" id="k_badgeLevel">—</span>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="button" id="btnPilih" onclick="loadKlasifikasiPreview()" disabled
                class="px-5 py-2.5 bg-violet-500 text-white text-sm font-semibold rounded-lg
                       shadow-md flex items-center gap-2 transition-all
                       disabled:opacity-40 disabled:cursor-not-allowed
                       enabled:hover:bg-violet-600 enabled:shadow-violet-400/30 active:scale-95">
                <i class="fas fa-search"></i> Pilih &amp; Tampilkan
            </button>
            <p class="text-xs text-gray-400" id="pilihHint">Pilih klasifikasi terlebih dahulu</p>
        </div>
    </div>

    {{-- ═══ SECTION 2: PREVIEW TABLE ═══ --}}
    {{-- ═══════════════════════════════════════════════════════
     SECTION 2 — PREVIEW TABLE (shared partial)
     Digunakan oleh: create-metadata, create-klasifikasi, create-wilayah
     Warna aksen dikontrol oleh variabel CSS --accent-* yang di-override
     di setiap halaman induk via <style> atau class.
═══════════════════════════════════════════════════════ --}}

{{-- TAB FREKUENSI --}}
<div id="previewSection" class="hidden mt-6">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-2">
            <div class="w-1 h-5 rounded-full bg-sky-500" id="accentBar"></div>
            <h3 class="font-bold text-gray-700 text-sm">Hasil Metadata Ditemukan</h3>
        </div>
        <span id="totalFound"
              class="text-xs px-2.5 py-1 rounded-full font-semibold border preview-badge">
            0 metadata
        </span>
    </div>

    {{-- TAB SWITCHER --}}
    <div class="border-b border-gray-200 mb-0">
        <div class="flex gap-0 overflow-x-auto" id="freqTabs">
            @foreach(['dekade' => 'Dekade', 'tahunan' => 'Tahunan', 'semester' => 'Semester', 'kuartal' => 'Kuartal', 'bulanan' => 'Bulanan'] as $key => $label)
                <button type="button"
                    id="tab-{{ $key }}"
                    onclick="switchTab('{{ $key }}')"
                    class="tab-btn shrink-0 px-4 py-2.5 text-xs font-semibold border-b-2 transition-all duration-150
                           border-transparent text-gray-400 cursor-not-allowed"
                    disabled>
                    {{ $label }}
                    <span id="tab-count-{{ $key }}"
                          class="ml-1.5 text-xs font-bold px-1.5 py-0.5 rounded-full bg-gray-100 text-gray-400 transition-colors">0</span>
                </button>
            @endforeach
        </div>
    </div>

    {{-- FILTER PERIODE --}}
    <div id="periodeFilter" class="hidden mb-0 px-4 py-3 bg-gray-50 border-x border-b border-gray-200 rounded-b-xl">
        <div class="flex flex-wrap items-center gap-2.5 text-sm">
            {{-- Dekade / Tahunan → hanya period_from - period_to --}}
            <div id="periodeSimple" class="flex items-center gap-2 hidden">
                <label class="text-xs text-gray-500 font-medium whitespace-nowrap">Rentang:</label>
                <input type="number" id="periodFromSimple" placeholder="Dari" min="1900" max="2100"
                       class="border border-gray-300 rounded-lg px-2.5 py-1.5 text-xs w-24
                              focus:outline-none focus:ring-2 focus:ring-sky-400 bg-white">
                <span class="text-gray-300 text-sm">—</span>
                <input type="number" id="periodToSimple" placeholder="Sampai" min="1900" max="2100"
                       class="border border-gray-300 rounded-lg px-2.5 py-1.5 text-xs w-24
                              focus:outline-none focus:ring-2 focus:ring-sky-400 bg-white">
            </div>
            {{-- Semester / Kuartal / Bulanan → tahun + period --}}
            <div id="periodeComplex" class="flex flex-wrap items-center gap-2 hidden">
                <label class="text-xs text-gray-500 font-medium whitespace-nowrap">Tahun:</label>
                <input type="number" id="yearFrom" placeholder="Dari" min="1900" max="2100"
                       class="border border-gray-300 rounded-lg px-2.5 py-1.5 text-xs w-20
                              focus:outline-none focus:ring-2 focus:ring-sky-400 bg-white">
                <span class="text-gray-300 text-sm">—</span>
                <input type="number" id="yearTo" placeholder="Sampai" min="1900" max="2100"
                       class="border border-gray-300 rounded-lg px-2.5 py-1.5 text-xs w-20
                              focus:outline-none focus:ring-2 focus:ring-sky-400 bg-white">
                <label class="text-xs text-gray-500 font-medium ml-2 whitespace-nowrap" id="periodeLabel">Periode:</label>
                <input type="number" id="periodFrom" placeholder="Dari" min="1" max="12"
                       class="border border-gray-300 rounded-lg px-2.5 py-1.5 text-xs w-16
                              focus:outline-none focus:ring-2 focus:ring-sky-400 bg-white">
                <span class="text-gray-300 text-sm">—</span>
                <input type="number" id="periodTo" placeholder="Sampai" min="1" max="12"
                       class="border border-gray-300 rounded-lg px-2.5 py-1.5 text-xs w-16
                              focus:outline-none focus:ring-2 focus:ring-sky-400 bg-white">
            </div>
            <div class="flex items-center gap-2 ml-auto">
                <button type="button" onclick="applyPeriodeFilter()"
                    class="px-3 py-1.5 preview-btn-primary text-white text-xs font-semibold rounded-lg
                           shadow-sm transition-all flex items-center gap-1.5">
                    <i class="fas fa-search text-xs"></i> Terapkan
                </button>
                <button type="button" onclick="resetPeriodeFilter()"
                    class="px-3 py-1.5 border border-gray-200 bg-white text-gray-500 hover:bg-gray-50
                           text-xs font-semibold rounded-lg transition-colors flex items-center gap-1.5">
                    <i class="fas fa-rotate-left text-xs"></i> Reset
                </button>
            </div>
        </div>
    </div>

    {{-- TABEL METADATA --}}
    <div class="mt-4 border border-gray-200 rounded-xl overflow-hidden shadow-sm">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                <tr class="border-b border-gray-200">
                    <th class="px-4 py-3 w-10">
                        <input type="checkbox" id="checkAllPreview" onchange="toggleAllPreview(this)"
                               class="rounded border-gray-300 cursor-pointer accent-sky-500">
                    </th>
                    <th class="px-4 py-3 font-semibold text-gray-600">Metadata</th>
                    <th class="px-4 py-3 font-semibold text-gray-600">Detail Wilayah</th>
                    <th class="px-4 py-3 font-semibold text-gray-600 text-center w-20">Aksi</th>
                </tr>
            </thead>
            <tbody id="previewTableBody" class="divide-y divide-gray-100 bg-white">
                <tr>
                    <td colspan="5" class="px-4 py-10 text-center text-gray-400 text-sm">
                        <div class="flex flex-col items-center gap-2">
                            <i class="fas fa-table text-gray-200 text-3xl"></i>
                            <span>Belum ada data. Klik <strong class="text-gray-500">"Pilih &amp; Tampilkan"</strong> untuk memuat metadata.</span>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- PAGINATION --}}
    <div id="previewPagination" class="hidden mt-3 flex items-center justify-between text-xs text-gray-500">
        <span id="paginationInfo" class="text-gray-400"></span>
        <div class="flex gap-1" id="paginationButtons"></div>
    </div>

    {{-- PENGATURAN URUTAN --}}
    <div class="mt-5 p-4 bg-gray-50 border border-gray-200 rounded-xl">
        <p class="text-xs font-semibold text-gray-600 mb-3 flex items-center gap-2">
            <i class="fas fa-sort-amount-down text-gray-400"></i> Pengaturan Urutan Tampilan
        </p>
        <div class="flex flex-wrap gap-4">
            <label class="flex items-center gap-2 cursor-pointer group">
                <input type="checkbox" name="urutan_by[]" value="klasifikasi"
                       class="rounded border-gray-300 accent-sky-500 cursor-pointer">
                <span class="text-xs text-gray-600 group-hover:text-gray-800 transition-colors">
                    Berdasarkan Klasifikasi
                </span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer group">
                <input type="checkbox" name="urutan_by[]" value="wilayah"
                       class="rounded border-gray-300 accent-sky-500 cursor-pointer">
                <span class="text-xs text-gray-600 group-hover:text-gray-800 transition-colors">
                    Berdasarkan Wilayah
                </span>
            </label>
        </div>
    </div>

    {{-- SELECTION BAR --}}
    <div id="selectionBarPreview"
         class="hidden mt-4 flex items-center justify-between px-4 py-2.5 rounded-xl text-sm
                bg-sky-50 border border-sky-200">
        <p class="text-sky-700 font-medium flex items-center gap-2">
            <i class="fas fa-check-square text-sky-500"></i>
            <span id="selectionCountPreview">0 metadata dipilih</span>
        </p>
        <button onclick="clearAllPreviewSelection()"
                class="text-xs font-medium text-sky-500 hover:text-sky-700 hover:underline transition-colors flex items-center gap-1">
            <i class="fas fa-times"></i> Batalkan Pilihan
        </button>
    </div>

    {{-- TOMBOL SIMPAN --}}
    <div class="mt-5 flex justify-end gap-3">
        <a href="{{ route('data.index') }}"
           class="border border-gray-200 bg-white text-gray-500 hover:bg-gray-50 hover:border-gray-300
                  px-5 py-2.5 rounded-lg text-sm font-medium transition-colors flex items-center gap-2">
            <i class="fas fa-arrow-left text-xs"></i> Kembali
        </a>
        <button type="button" onclick="openSaveModal()"
            class="preview-btn-primary px-6 py-2.5 text-white text-sm font-semibold rounded-lg
                   shadow-md transition-all flex items-center gap-2 hover:shadow-lg active:scale-95">
            <i class="fas fa-bookmark"></i> Simpan Template
        </button>
    </div>
</div>

{{-- ═══ MODAL SIMPAN TEMPLATE ═══ --}}
<div id="modalSaveTemplate"
     class="fixed inset-0 z-50 hidden items-center justify-center p-4"
     style="background: rgba(0,0,0,0.5); backdrop-filter: blur(2px);">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md animate-modal">

        {{-- Modal Header --}}
        <div class="preview-modal-header px-6 py-4 rounded-t-2xl">
            <div class="flex items-center justify-between">
                <h3 class="text-white font-bold text-base flex items-center gap-2.5">
                    <div class="w-7 h-7 bg-white/20 rounded-lg flex items-center justify-center">
                        <i class="fas fa-bookmark text-white text-xs"></i>
                    </div>
                    Simpan Template
                </h3>
                <button onclick="closeSaveModal()"
                        class="text-white/70 hover:text-white text-2xl leading-none w-8 h-8 flex items-center
                               justify-center rounded-lg hover:bg-white/10 transition-colors">×</button>
            </div>
        </div>

        {{-- Modal Body --}}
        <div class="p-6 space-y-4">
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5 uppercase tracking-wide">
                    Nama Template <span class="text-red-500">*</span>
                </label>
                <input type="text" id="inputNamaTemplate"
                       placeholder="cth: Data Ekonomi Bali 2020–2024"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm
                              focus:outline-none focus:ring-2 focus:ring-sky-400 transition-shadow">
                <p id="errorNamaTemplate" class="hidden mt-1.5 text-xs text-red-500 flex items-center gap-1">
                    <i class="fas fa-exclamation-circle"></i> Nama template wajib diisi.
                </p>
            </div>

            <div id="saveSummary"
                 class="p-3.5 rounded-xl border preview-summary-box text-xs flex items-center gap-3">
                <div class="w-8 h-8 preview-summary-icon rounded-lg flex items-center justify-center shrink-0">
                    <i class="fas fa-layer-group text-sm"></i>
                </div>
                <div>
                    <p class="font-semibold preview-summary-text">
                        <span id="saveMetadataCount">0</span> metadata akan disimpan
                    </p>
                    <p class="preview-summary-sub mt-0.5">Template dapat diakses kembali dari halaman Data</p>
                </div>
            </div>

            {{-- Info untuk guest --}}
            @guest
            <div class="p-3 bg-amber-50 border border-amber-200 rounded-lg text-xs text-amber-700 flex items-start gap-2">
                <i class="fas fa-circle-info text-amber-500 mt-0.5 shrink-0"></i>
                <span>Anda belum login. Template akan disimpan di browser ini saja.
                    <a href="{{ route('login') }}" class="underline font-semibold">Login</a> untuk menyimpan ke server.</span>
            </div>
            @endguest
        </div>

        {{-- Modal Footer --}}
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-end gap-2.5 rounded-b-2xl">
            <button onclick="closeSaveModal()"
                class="border border-gray-200 bg-white text-gray-500 hover:bg-gray-100 hover:border-gray-300
                       px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                Batal
            </button>
            <button onclick="submitSaveTemplate()"
                class="preview-btn-primary px-5 py-2 rounded-lg text-sm font-semibold text-white
                       flex items-center gap-2 transition-all hover:shadow-md active:scale-95">
                <i class="fas fa-bookmark text-xs"></i> Simpan
            </button>
        </div>
    </div>
</div>

</div>

{{-- HIDDEN FORM --}}
<form id="formSaveTemplateHidden" action="{{ route('template.store') }}" method="POST" class="hidden">
    @csrf
    <input type="hidden" name="jenis_template" value="klasifikasi">
    <input type="hidden" name="nama_tampilan"  id="hidNama">
    <input type="hidden" name="klasifikasi"    id="hidKlasifikasi">
    <div id="hidMetadataIds"></div>
    <div id="hidLocationIds"></div>
    <div id="hidUrutanBy"></div>
</form>

<script>
// ─────────────────────────────────────────────────────────────
// ENDPOINTS & CONFIG
// ─────────────────────────────────────────────────────────────
const FETCH_KLASIFIKASI_URL = '{{ route("template.fetch_klasifikasi") }}';
const FETCH_PREVIEW_URL     = '{{ route("template.fetch_preview") }}';
const CSRF                  = '{{ csrf_token() }}';
const IS_LOGGED_IN          = {{ Auth::check() ? 'true' : 'false' }};

// ─────────────────────────────────────────────────────────────
// CASCADE WILAYAH — native <select>, tidak ada spinner/loading
// ─────────────────────────────────────────────────────────────
const K_LEVELS = ['provinsi', 'kabupaten', 'kecamatan', 'desa'];
const K_LEVEL_LABEL = {
    provinsi : 'Provinsi',
    kabupaten: 'Kabupaten/Kota',
    kecamatan: 'Kecamatan',
    desa     : 'Desa/Kelurahan',
};
const K_URLS = {
    kabupaten: '{{ route("template.get_kabupaten") }}',
    kecamatan: '{{ route("template.get_kecamatan_wil") }}',
    desa     : '{{ route("template.get_desa_wil") }}',
};

const kSelLoc = { provinsi: null, kabupaten: null, kecamatan: null, desa: null };
const kCaches = { kabupaten: {}, kecamatan: {}, desa: {} };

function kCap(s) { return s.charAt(0).toUpperCase() + s.slice(1); }

async function kSelectFromNative(level, id, nama) {
    if (!id) {
        kClearFromLevel(level);
        return;
    }

    kSelLoc[level] = { id, nama };
    document.getElementById('k_val' + kCap(level)).value = id;

    // Reset level di bawah
    const idx = K_LEVELS.indexOf(level);
    K_LEVELS.slice(idx + 1).forEach(l => kResetLevel(l));

    // Isi level berikutnya
    const next = K_LEVELS[idx + 1];
    if (next && K_URLS[next]) {
        await kFillSelect(next, id);
    }

    kUpdateBadge();
}

async function kFillSelect(level, parentId) {
    const sel = document.getElementById('k_select' + kCap(level));

    if (kCaches[level][parentId]) {
        kRenderOptions(sel, level, kCaches[level][parentId]);
        return;
    }

    sel.disabled = true;
    sel.classList.add('opacity-60');

    const paramKey = level === 'kabupaten' ? 'provinsi_id'
                   : level === 'kecamatan' ? 'kabupaten_id'
                   : 'kecamatan_id';

    try {
        const r = await fetch(`${K_URLS[level]}?${paramKey}=${parentId}`);
        const d = await r.json();
        kCaches[level][parentId] = d;
        kRenderOptions(sel, level, d);
    } catch {
        sel.innerHTML = '<option value="">Gagal memuat</option>';
    } finally {
        sel.disabled = false;
        sel.classList.remove('opacity-60', 'bg-gray-100', 'text-gray-400', 'cursor-not-allowed');
        sel.classList.add('bg-white', 'text-gray-700');
    }
}

function kRenderOptions(sel, level, items) {
    sel.innerHTML = `<option value="">— Pilih ${K_LEVEL_LABEL[level]} —</option>` +
        items.map(x => `<option value="${x.location_id}">${escHtml(x.nama_wilayah)}</option>`).join('');
}

function kResetLevel(level) {
    kSelLoc[level] = null;
    document.getElementById('k_val' + kCap(level)).value = '';
    const sel = document.getElementById('k_select' + kCap(level));
    sel.innerHTML = `<option value="">— Pilih ${K_LEVEL_LABEL[K_LEVELS[K_LEVELS.indexOf(level)-1]]} dulu —</option>`;
    sel.disabled = true;
    sel.classList.add('bg-gray-100', 'text-gray-400', 'cursor-not-allowed');
    sel.classList.remove('bg-white', 'text-gray-700');
}

function kClearFromLevel(level) {
    const idx = K_LEVELS.indexOf(level);
    K_LEVELS.slice(idx).forEach(l => {
        kSelLoc[l] = null;
        document.getElementById('k_val' + kCap(l)).value = '';
        if (l !== 'provinsi') {
            const sel = document.getElementById('k_select' + kCap(l));
            sel.innerHTML = `<option value="">— Pilih ${K_LEVEL_LABEL[K_LEVELS[K_LEVELS.indexOf(l)-1]]} dulu —</option>`;
            sel.disabled = true;
            sel.classList.add('bg-gray-100', 'text-gray-400', 'cursor-not-allowed');
            sel.classList.remove('bg-white', 'text-gray-700');
        } else {
            document.getElementById('k_selectProvinsi').value = '';
        }
    });
    kUpdateBadge();
}

function kUpdateBadge() {
    let deepest = null;
    for (let i = K_LEVELS.length - 1; i >= 0; i--) {
        if (kSelLoc[K_LEVELS[i]]) { deepest = { level: K_LEVELS[i], ...kSelLoc[K_LEVELS[i]] }; break; }
    }
    const badge = document.getElementById('k_selectedWilayahBadge');
    if (deepest) {
        badge.classList.remove('hidden');
        document.getElementById('k_badgeNama').textContent  = deepest.nama;
        document.getElementById('k_badgeLevel').textContent = '(' + K_LEVEL_LABEL[deepest.level] + ')';
    } else {
        badge.classList.add('hidden');
    }
}

function kGetDeepestLocId() {
    for (let i = K_LEVELS.length - 1; i >= 0; i--) {
        if (kSelLoc[K_LEVELS[i]]) return kSelLoc[K_LEVELS[i]].id;
    }
    return null;
}

// Enable tombol Pilih saat klasifikasi dipilih
function onKlasifikasiChange() {
    const val = document.getElementById('klasifikasiSelect').value;
    const btn = document.getElementById('btnPilih');
    const hint = document.getElementById('pilihHint');
    if (val) {
        btn.disabled = false;
        hint.textContent = 'Wilayah bersifat opsional';
    } else {
        btn.disabled = true;
        hint.textContent = 'Pilih klasifikasi terlebih dahulu';
    }
}

// ─────────────────────────────────────────────────────────────
// LOAD KLASIFIKASI PREVIEW
// ─────────────────────────────────────────────────────────────
let groupedData          = {};
let activeTab            = '';
let currentPage          = {};
const PAGE_SIZE          = 10;
let selectedPreviewItems = {};

async function loadKlasifikasiPreview() {
    const klasifikasi = document.getElementById('klasifikasiSelect').value;
    if (!klasifikasi) { alert('Pilih klasifikasi terlebih dahulu.'); return; }

    const body  = new URLSearchParams();
    body.append('_token', CSRF);
    body.append('klasifikasi', klasifikasi);

    const locId = kGetDeepestLocId();
    if (locId) body.append('location_ids[]', locId);

    try {
        const r = await fetch(FETCH_KLASIFIKASI_URL, { method: 'POST', body });
        const d = await r.json();
        if (!d.success) { alert('Gagal memuat preview.'); return; }
        groupedData = d.grouped;
        renderPreviewSection();
    } catch(e) { alert('Terjadi kesalahan: ' + e.message); }
}

// ─────────────────────────────────────────────────────────────
// SHARED PREVIEW TABLE LOGIC
// ─────────────────────────────────────────────────────────────
const FREQ_LABELS = { dekade:'Dekade', tahunan:'Tahunan', semester:'Semester', kuartal:'Kuartal', bulanan:'Bulanan' };

function renderPreviewSection() {
    document.getElementById('previewSection').classList.remove('hidden');
    let total = 0, firstActiveTab = '';

    Object.entries(groupedData).forEach(([freq, items]) => {
        const count    = items.length;
        total         += count;
        const tabBtn   = document.getElementById('tab-' + freq);
        const tabCount = document.getElementById('tab-count-' + freq);
        tabCount.textContent = count;

        if (count > 0) {
            tabBtn.disabled = false;
            tabBtn.classList.remove('cursor-not-allowed', 'text-gray-400');
            tabBtn.classList.add('cursor-pointer', 'text-gray-600', 'hover:text-gray-800');
            tabCount.classList.remove('bg-gray-100', 'text-gray-400');
            tabCount.classList.add('bg-violet-100', 'text-violet-600');
            if (!firstActiveTab) firstActiveTab = freq;
        } else {
            tabBtn.disabled = true;
            tabBtn.classList.add('cursor-not-allowed', 'text-gray-400');
            tabCount.classList.add('bg-gray-100', 'text-gray-400');
            tabCount.classList.remove('bg-violet-100', 'text-violet-600');
        }
    });

    document.getElementById('totalFound').textContent = total + ' metadata';
    if (firstActiveTab) switchTab(firstActiveTab);
}

function switchTab(freq) {
    activeTab = freq;
    Object.keys(FREQ_LABELS).forEach(f => {
        const btn = document.getElementById('tab-' + f);
        if (f === freq) {
            btn.classList.add('border-violet-500', 'text-violet-600');
            btn.classList.remove('border-transparent', 'text-gray-600');
        } else {
            btn.classList.remove('border-violet-500', 'text-violet-600');
            if (!btn.disabled) btn.classList.add('border-transparent', 'text-gray-600');
        }
    });

    const pf = document.getElementById('periodeFilter');
    const sd = document.getElementById('periodeSimple');
    const cd = document.getElementById('periodeComplex');
    pf.classList.remove('hidden');

    if (['dekade','tahunan'].includes(freq)) {
        sd.classList.remove('hidden'); cd.classList.add('hidden');
    } else {
        sd.classList.add('hidden'); cd.classList.remove('hidden');
        const pl = document.getElementById('periodeLabel');
        if (pl) pl.textContent = freq === 'semester' ? 'Semester (1-2):' : freq === 'kuartal' ? 'Kuartal (1-4):' : 'Bulan (1-12):';
        const pf_ = document.getElementById('periodFrom');
        const pt_ = document.getElementById('periodTo');
        if (pf_) pf_.max = freq === 'semester' ? 2 : freq === 'kuartal' ? 4 : 12;
        if (pt_) pt_.max = freq === 'semester' ? 2 : freq === 'kuartal' ? 4 : 12;
    }

    currentPage[freq] = currentPage[freq] || 1;
    renderTable(freq, currentPage[freq]);
}

function renderTable(freq, page) {
    const items = groupedData[freq] || [];
    const tbody = document.getElementById('previewTableBody');

    if (!items.length) {
        tbody.innerHTML = `<tr><td colspan="5" class="px-4 py-10 text-center text-gray-400 text-sm">
            <div class="flex flex-col items-center gap-2">
                <i class="fas fa-inbox text-gray-200 text-3xl"></i>
                <span>Tidak ada metadata dengan frekuensi <strong>${FREQ_LABELS[freq]}</strong></span>
            </div>
        </td></tr>`;
        document.getElementById('previewPagination').classList.add('hidden');
        return;
    }

    const start = (page - 1) * PAGE_SIZE;
    const end   = start + PAGE_SIZE;
    const paged = items.slice(start, end);

    tbody.innerHTML = paged.map(m => {
        const locs = m.locations && m.locations.length
            ? m.locations
            : [{ location_id: 0, nama_wilayah: 'Semua Wilayah', has_children: false }];

        return locs.map((loc, li) => {
            const rowKey  = `${m.metadata_id}_${loc.location_id}`;
            const checked = !!selectedPreviewItems[rowKey];

            return `<tr class="hover:bg-violet-50/40 transition-colors">
                ${li === 0 ? `
                <td class="px-4 py-3 align-top" rowspan="${locs.length}">
                    <input type="checkbox" class="preview-check rounded border-gray-300 accent-violet-500 cursor-pointer"
                        value="${rowKey}" data-meta-id="${m.metadata_id}" data-loc-id="${loc.location_id}"
                        data-meta-nama="${escHtml(m.nama)}" data-loc-nama="${escHtml(loc.nama_wilayah)}"
                        onchange="onPreviewCheck(this)" ${checked ? 'checked' : ''}>
                </td>
                <td class="px-4 py-3 align-top" rowspan="${locs.length}">
                    <p class="font-semibold text-gray-800 text-xs leading-snug">${escHtml(m.nama)}</p>
                    <p class="text-xs text-gray-400 mt-0.5">
                        ${escHtml(m.satuan_data||'—')}
                        <span class="mx-1 text-gray-300">·</span>
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-violet-50 text-violet-600 text-xs font-medium">
                            ${escHtml(m.frekuensi_penerbitan||'')}
                        </span>
                    </p>
                </td>
                <td class="px-4 py-3 align-top text-xs text-gray-500" rowspan="${locs.length}">
                    
                </td>` : ''}
                <td class="px-4 py-3 text-xs">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-map-marker-alt text-gray-300 text-xs shrink-0"></i>
                        <span class="text-gray-700">${escHtml(loc.nama_wilayah)}</span>
                        ${loc.has_children ? `
                        <button type="button" onclick="expandLocation(this, ${m.metadata_id}, ${loc.location_id})"
                            class="text-violet-400 hover:text-violet-600 transition-colors shrink-0" title="Lihat turunan">
                            <i class="fas fa-chevron-down text-xs"></i>
                        </button>` : ''}
                    </div>
                </td>
                <td class="px-4 py-3 text-center text-xs">
                    <span class="text-gray-300 text-base">—</span>
                </td>
            </tr>`;
        }).join('');
    }).join('');

    const totalPages = Math.ceil(items.length / PAGE_SIZE);
    const paginDiv   = document.getElementById('previewPagination');

    if (totalPages > 1) {
        paginDiv.classList.remove('hidden');
        document.getElementById('paginationInfo').textContent =
            `Menampilkan ${start+1}–${Math.min(end, items.length)} dari ${items.length}`;
        let html = '';
        for (let p = 1; p <= totalPages; p++) {
            html += `<button type="button" onclick="goPage(${p})"
                class="w-7 h-7 text-xs rounded-lg font-medium transition-colors
                       ${p === page ? 'bg-violet-500 text-white shadow-sm' : 'border border-gray-200 text-gray-500 hover:bg-gray-50'}">${p}</button>`;
        }
        document.getElementById('paginationButtons').innerHTML = html;
    } else {
        paginDiv.classList.add('hidden');
    }
}

function goPage(p) { currentPage[activeTab] = p; renderTable(activeTab, p); }

// ─────────────────────────────────────────────────────────────
// FILTER PERIODE
// ─────────────────────────────────────────────────────────────
async function applyPeriodeFilter() {
    const klasifikasi = document.getElementById('klasifikasiSelect').value;
    if (!klasifikasi) return;

    const body = new URLSearchParams();
    body.append('_token', CSRF);
    body.append('klasifikasi', klasifikasi);
    body.append('frekuensi', activeTab);

    const locId = kGetDeepestLocId();
    if (locId) body.append('location_ids[]', locId);

    if (['dekade','tahunan'].includes(activeTab)) {
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

    try {
        const r = await fetch(FETCH_KLASIFIKASI_URL, { method: 'POST', body });
        const d = await r.json();
        if (!d.success) return;
        groupedData = d.grouped;
        currentPage = {};
        renderPreviewSection();
    } catch(e) { console.error(e); }
}

function resetPeriodeFilter() {
    ['periodFromSimple','periodToSimple','yearFrom','yearTo','periodFrom','periodTo']
        .forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
    loadKlasifikasiPreview();
}

// ─────────────────────────────────────────────────────────────
// EXPAND CHILD LOCATIONS
// ─────────────────────────────────────────────────────────────
async function expandLocation(btn, metadataId, locationId) {
    const icon     = btn.querySelector('i');
    const isOpen   = icon.classList.contains('fa-chevron-up');
    const existing = document.getElementById(`child-${metadataId}-${locationId}`);

    if (isOpen && existing) {
        existing.remove();
        icon.classList.replace('fa-chevron-up', 'fa-chevron-down');
        return;
    }
    icon.classList.replace('fa-chevron-down', 'fa-chevron-up');

    try {
        const r = await fetch(`{{ route("template.child_locations") }}?metadata_id=${metadataId}&location_id=${locationId}`);
        const d = await r.json();
        if (!d.children || !d.children.length) { icon.classList.replace('fa-chevron-up', 'fa-chevron-down'); return; }

        const parentRow  = btn.closest('tr');
        const childTbody = document.createElement('tbody');
        childTbody.id = `child-${metadataId}-${locationId}`;

        d.children.forEach(child => {
            const childKey = `${metadataId}_${child.location_id}`;
            const checked  = !!selectedPreviewItems[childKey];
            const tr = document.createElement('tr');
            tr.className = 'bg-violet-50/40 text-xs border-t border-violet-100';
            tr.innerHTML = `
                <td class="px-4 py-2 pl-8">
                    <input type="checkbox" class="preview-check rounded border-gray-300 accent-violet-500 cursor-pointer"
                        value="${childKey}" data-meta-id="${metadataId}" data-loc-id="${child.location_id}"
                        data-meta-nama="" data-loc-nama="${escHtml(child.nama_wilayah)}"
                        onchange="onPreviewCheck(this)" ${checked ? 'checked' : ''}>
                </td>
                <td class="px-4 py-2"></td>
                <td class="px-4 py-2"></td>
                <td class="px-4 py-2">
                    <div class="flex items-center gap-2 pl-4">
                        <i class="fas fa-level-up-alt fa-rotate-90 text-violet-300 text-xs"></i>
                        <span class="text-gray-600">${escHtml(child.nama_wilayah)}</span>
                        ${child.has_children ? `<button type="button" onclick="expandLocation(this, ${metadataId}, ${child.location_id})"
                            class="text-violet-400 hover:text-violet-600 transition-colors">
                            <i class="fas fa-chevron-down text-xs"></i></button>` : ''}
                    </div>
                </td>
                <td class="px-4 py-2 text-center"><span class="text-gray-300 text-base">—</span></td>
            `;
            childTbody.appendChild(tr);
        });

        parentRow.parentNode.insertBefore(childTbody, parentRow.nextSibling);
    } catch(e) {
        console.error(e);
        icon.classList.replace('fa-chevron-up', 'fa-chevron-down');
    }
}

// ─────────────────────────────────────────────────────────────
// SELECTION
// ─────────────────────────────────────────────────────────────
function onPreviewCheck(cb) {
    const key = cb.value;
    if (cb.checked) {
        selectedPreviewItems[key] = {
            key, metadataId: cb.dataset.metaId, locationId: cb.dataset.locId,
            metaNama: cb.dataset.metaNama, locNama: cb.dataset.locNama,
        };
    } else {
        delete selectedPreviewItems[key];
    }
    updatePreviewSelBar();
}

function toggleAllPreview(masterCb) {
    document.querySelectorAll('.preview-check').forEach(cb => { cb.checked = masterCb.checked; onPreviewCheck(cb); });
}

function clearAllPreviewSelection() {
    selectedPreviewItems = {};
    document.querySelectorAll('.preview-check').forEach(cb => cb.checked = false);
    const ca = document.getElementById('checkAllPreview');
    if (ca) ca.checked = false;
    updatePreviewSelBar();
}

function updatePreviewSelBar() {
    const count = Object.keys(selectedPreviewItems).length;
    const bar   = document.getElementById('selectionBarPreview');
    if (bar) bar.classList.toggle('hidden', count === 0);
    const sc = document.getElementById('selectionCountPreview');
    if (sc) sc.textContent = count + ' metadata dipilih';
}

// ─────────────────────────────────────────────────────────────
// SAVE MODAL
// ─────────────────────────────────────────────────────────────
function openSaveModal() {
    const checked = document.querySelectorAll('.preview-check:checked');
    if (!checked.length) { alert('Pilih minimal 1 metadata terlebih dahulu.'); return; }
    document.getElementById('saveMetadataCount').textContent = checked.length;
    document.getElementById('inputNamaTemplate').value = '';
    document.getElementById('errorNamaTemplate').classList.add('hidden');
    document.getElementById('modalSaveTemplate').classList.remove('hidden');
    setTimeout(() => document.getElementById('inputNamaTemplate').focus(), 100);
}

function closeSaveModal() { document.getElementById('modalSaveTemplate').classList.add('hidden'); }

async function submitSaveTemplate() {
    const nama = document.getElementById('inputNamaTemplate').value.trim();
    if (!nama) { document.getElementById('errorNamaTemplate').classList.remove('hidden'); return; }

    const metaSet  = new Set([...document.querySelectorAll('.preview-check:checked')].map(cb => cb.dataset.metaId));
    const locId    = kGetDeepestLocId();
    const urutanBy = [...document.querySelectorAll('input[name="urutan_by[]"]:checked')].map(c => c.value);

    if (IS_LOGGED_IN) {
        document.getElementById('hidNama').value        = nama;
        document.getElementById('hidKlasifikasi').value = document.getElementById('klasifikasiSelect').value;
        document.getElementById('hidMetadataIds').innerHTML =
            [...metaSet].map(id => `<input type="hidden" name="metadata_ids[]" value="${id}">`).join('');
        document.getElementById('hidLocationIds').innerHTML =
            locId ? `<input type="hidden" name="location_ids[]" value="${locId}">` : '';
        document.getElementById('hidUrutanBy').innerHTML =
            urutanBy.map(v => `<input type="hidden" name="urutan_by[]" value="${v}">`).join('');
        document.getElementById('formSaveTemplateHidden').submit();
    } else {
        const body = new URLSearchParams();
        body.append('_token', CSRF);
        body.append('nama_tampilan', nama);
        body.append('jenis_template', 'klasifikasi');
        body.append('klasifikasi', document.getElementById('klasifikasiSelect').value);
        [...metaSet].forEach(id => body.append('metadata_ids[]', id));
        if (locId) body.append('location_ids[]', locId);
        urutanBy.forEach(v => body.append('urutan_by[]', v));

        try {
            const r = await fetch('{{ route("template.store") }}', { method: 'POST', body });
            const d = await r.json();
            if (d.success && d.storage === 'local') {
                const existing = JSON.parse(localStorage.getItem('savedTemplates') || '[]');
                d.template_data.local_id = 'tmpl_' + Date.now();
                existing.push(d.template_data);
                localStorage.setItem('savedTemplates', JSON.stringify(existing));
                alert(`Template "${nama}" disimpan di browser.\n(Login untuk menyimpan ke server)`);
                closeSaveModal();
                window.location.href = d.redirect;
            }
        } catch(e) { alert('Gagal menyimpan: ' + e.message); }
    }
}

// ─────────────────────────────────────────────────────────────
// UTILS
// ─────────────────────────────────────────────────────────────
function escHtml(str) {
    const d = document.createElement('div');
    d.innerText = str || '';
    return d.innerHTML;
}

// Tutup modal saat klik backdrop
document.addEventListener('click', e => {
    if (e.target === document.getElementById('modalSaveTemplate')) closeSaveModal();
});
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeSaveModal(); });
</script>

@endsection