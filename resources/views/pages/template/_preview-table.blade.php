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
                    <th class="px-4 py-3 font-semibold text-gray-600">Klasifikasi</th>
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

<style>
    /* ── Animasi modal ── */
    @keyframes modalIn {
        from { opacity: 0; transform: scale(0.95) translateY(8px); }
        to   { opacity: 1; transform: scale(1) translateY(0); }
    }
    .animate-modal { animation: modalIn .18s ease-out both; }

    /* ── Flex fix untuk modal overlay ── */
    #modalSaveTemplate:not(.hidden) { display: flex; }
</style>