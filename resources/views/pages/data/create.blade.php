@extends('layouts.main')

@section('content')
<div class="py-6">

    <a href="{{ route('data.index') }}"
       class="flex items-center gap-1 font-semibold text-sky-600 ps-4 mb-4 hover:text-sky-900 text-sm transition-colors">
        <i class="fas fa-angle-left"></i> Kembali
    </a>

    <div class="mt-2 bg-white rounded-xl shadow p-6">

        <h1 class="text-xl font-bold text-gray-800 mb-1">Input Data</h1>
        <p class="text-sm text-gray-400 mb-6">Data akan menunggu verifikasi admin sebelum ditampilkan</p>

        {{-- DUPLICATE WARNING --}}
        @if(session('duplicate_warning'))
            <div class="mb-5 bg-amber-50 border border-amber-300 rounded-lg p-4">
                <div class="flex items-start gap-3">
                    <i class="fas fa-exclamation-triangle text-amber-500 mt-0.5 shrink-0"></i>
                    <div>
                        <p class="font-semibold text-amber-800 text-sm">Data Duplikat Terdeteksi</p>
                        <p class="text-amber-700 text-sm mt-1">{{ session('duplicate_warning.message') }}</p>
                        <p class="text-amber-600 text-xs mt-1">
                            Data existing ID #{{ session('duplicate_warning.existing_id') }} —
                            Status: <strong>{{ session('duplicate_warning.existing_status') }}</strong>
                        </p>
                        <div class="flex gap-2 mt-3">
                            <a href="{{ route('data.show', session('duplicate_warning.existing_id')) }}"
                               class="text-xs bg-amber-100 hover:bg-amber-200 text-amber-700 px-3 py-1.5
                                      rounded-md font-medium transition-colors">
                                <i class="fas fa-eye mr-1"></i> Lihat Data Existing
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- ══════════════════════════════════════════════
             TAB SWITCHER
        ══════════════════════════════════════════════ --}}
        <div class="flex border-b border-gray-200 mb-6">
            <button onclick="switchTab('manual')" id="tab-manual"
                class="tab-btn px-5 py-2.5 text-sm font-semibold border-b-2 transition-colors
                       border-sky-500 text-sky-600">
                <i class="fas fa-keyboard mr-2"></i>Input Manual
            </button>
            <button onclick="switchTab('excel')" id="tab-excel"
                class="tab-btn px-5 py-2.5 text-sm font-semibold border-b-2 transition-colors
                       border-transparent text-gray-400 hover:text-gray-600">
                <i class="fas fa-file-excel mr-2"></i>Upload Excel
            </button>
        </div>

        {{-- ══════════════════════════════════════════════
             TAB 1: INPUT MANUAL
        ══════════════════════════════════════════════ --}}
        <div id="panel-manual">
            <form action="{{ route('data.store') }}" method="POST" class="space-y-5">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                    {{-- Metadata --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                            Metadata <span class="text-red-500">*</span>
                        </label>
                        <select name="metadata_id" id="metadataSelect" required
                            class="w-full border @error('metadata_id') border-red-400 @else border-gray-300 @enderror
                                   rounded-md px-3 py-2.5 text-sm focus:outline-none focus:ring-2
                                   focus:ring-sky-400 bg-white"
                            onchange="updateMetadataInfo(this)">
                            <option value="">-- Pilih Metadata --</option>
                            @foreach($metadataList as $meta)
                                <option value="{{ $meta->metadata_id }}"
                                    data-tipe="{{ $meta->tipe_data }}"
                                    data-satuan="{{ $meta->satuan_data }}"
                                    {{ old('metadata_id') == $meta->metadata_id ? 'selected' : '' }}>
                                    {{ $meta->nama }}
                                </option>
                            @endforeach
                        </select>
                        @error('metadata_id')
                            <p class="mt-1 text-xs text-red-500">
                                <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                            </p>
                        @enderror
                        <div id="metadataInfo"
                             class="hidden mt-2 px-3 py-2 bg-sky-50 border border-sky-100
                                    rounded-md text-xs text-sky-700">
                            <span id="metadataTipe"></span> •
                            Satuan: <span id="metadataSatuan"></span>
                        </div>
                    </div>

                    {{-- Lokasi --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                            Lokasi <span class="text-red-500">*</span>
                        </label>
                        <select name="location_id" required
                            class="w-full border @error('location_id') border-red-400 @else border-gray-300 @enderror
                                   rounded-md px-3 py-2.5 text-sm focus:outline-none focus:ring-2
                                   focus:ring-sky-400 bg-white">
                            <option value="">-- Pilih Lokasi --</option>
                            @foreach($locationList as $loc)
                                <option value="{{ $loc->location_id }}"
                                    {{ old('location_id') == $loc->location_id ? 'selected' : '' }}>
                                    {{ $loc->kabupaten }} — {{ $loc->kecamatan }}, {{ $loc->desa }}
                                </option>
                            @endforeach
                        </select>
                        @error('location_id')
                            <p class="mt-1 text-xs text-red-500">
                                <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Waktu --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                            Waktu <span class="text-red-500">*</span>
                        </label>
                        <div class="flex gap-2">
                            <select id="filterTahun"
                                class="border border-gray-300 rounded-md px-3 py-2.5 text-sm
                                       focus:outline-none focus:ring-2 focus:ring-sky-400 bg-white w-1/3"
                                onchange="filterWaktu()">
                                <option value="">Tahun</option>
                                @foreach($timeList->pluck('year')->unique()->sortDesc() as $yr)
                                    <option value="{{ $yr }}">{{ $yr }}</option>
                                @endforeach
                            </select>
                            <select id="filterBulan"
                                class="border border-gray-300 rounded-md px-3 py-2.5 text-sm
                                       focus:outline-none focus:ring-2 focus:ring-sky-400 bg-white w-1/3"
                                onchange="filterWaktu()">
                                <option value="">Bulan</option>
                                @foreach(['Januari','Februari','Maret','April','Mei','Juni',
                                          'Juli','Agustus','September','Oktober','November','Desember']
                                         as $i => $bulan)
                                    <option value="{{ $i + 1 }}">{{ $bulan }}</option>
                                @endforeach
                            </select>
                            <select name="time_id" id="selectHari" required
                                class="border @error('time_id') border-red-400 @else border-gray-300 @enderror
                                       rounded-md px-3 py-2.5 text-sm focus:outline-none focus:ring-2
                                       focus:ring-sky-400 bg-white flex-1">
                                <option value="">Hari</option>
                                @foreach($timeList as $t)
                                    <option value="{{ $t->time_id }}"
                                        data-year="{{ $t->year }}"
                                        data-month="{{ $t->month }}"
                                        {{ old('time_id') == $t->time_id ? 'selected' : '' }}>
                                        {{ $t->day }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @error('time_id')
                            <p class="mt-1 text-xs text-red-500">
                                <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Nilai Angka --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                            Nilai Angka
                            <span id="satuanLabel" class="text-gray-400 font-normal text-xs ml-1"></span>
                        </label>
                        <input type="number" name="number_value" step="0.01"
                            value="{{ old('number_value') }}"
                            placeholder="Contoh: 1250.50"
                            class="w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm
                                   focus:outline-none focus:ring-2 focus:ring-sky-400">
                        @error('number_value')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit"
                        class="bg-sky-600 hover:bg-sky-700 text-white px-6 py-2.5 rounded-md shadow
                               text-sm font-semibold flex items-center gap-2 transition-colors">
                        <i class="fas fa-save"></i> Simpan Data
                    </button>
                </div>
            </form>
        </div>

        {{-- ══════════════════════════════════════════════
             TAB 2: UPLOAD EXCEL
             Preview dilakukan via AJAX ke server (DataImport).
             Server membaca file, melakukan lookup time_id, deteksi
             duplikat, dan mengembalikan JSON siap-render.
        ══════════════════════════════════════════════ --}}
        <div id="panel-excel" class="hidden">

            {{-- Info Format Template --}}
            <div class="mb-5 rounded-lg border p-4 text-sm"
                 style="background:#f0f9ff; border-color:#bae6fd;">
                <p class="font-semibold flex items-center gap-2 mb-2" style="color:#0369a1;">
                    <i class="fas fa-info-circle"></i>
                    Format Excel Template Metadata
                </p>
                <p class="text-xs text-gray-600 mb-3">
                    Gunakan file template yang di-generate dari halaman
                    <strong>Daftar Metadata → Export Template</strong>.
                    Header ada di <strong>baris 3</strong> dengan struktur kolom:
                </p>
                <div class="flex flex-wrap gap-1.5 mb-3">
                    @foreach(['metadata_id','nama_metadata','location_id','nama_lokasi'] as $col)
                        <code class="px-2 py-0.5 rounded text-xs font-mono font-bold"
                              style="background:#e0f2fe; color:#0369a1;">{{ $col }}</code>
                    @endforeach
                    <code class="px-2 py-0.5 rounded text-xs font-mono"
                          style="background:#fef3c7; color:#92400e;">2021</code>
                    <code class="px-2 py-0.5 rounded text-xs font-mono"
                          style="background:#fef3c7; color:#92400e;">2022</code>
                    <code class="px-2 py-0.5 rounded text-xs font-mono"
                          style="background:#fef3c7; color:#92400e;">… dst</code>
                </div>
                <p class="text-xs text-gray-500">
                    Format kolom periode yang didukung:
                    <code class="bg-gray-100 px-1 rounded">2022</code> (tahunan) ·
                    <code class="bg-gray-100 px-1 rounded">2022_Q1</code> (quarter) ·
                    <code class="bg-gray-100 px-1 rounded">2022_S1</code> (semester) ·
                    <code class="bg-gray-100 px-1 rounded">Jan_2022</code> (bulanan)
                </p>
            </div>

            {{-- Drop Zone --}}
            <div id="dropZone"
                 class="border-2 border-dashed border-gray-300 rounded-xl p-10 text-center
                        transition-colors cursor-pointer"
                 style="hover:border-sky-400; hover:background:#f0f9ff;"
                 onclick="document.getElementById('fileExcel').click()"
                 ondragover="event.preventDefault(); this.style.borderColor='#38bdf8'; this.style.background='#f0f9ff';"
                 ondragleave="this.style.borderColor=''; this.style.background='';"
                 ondrop="handleDrop(event)">
                <i class="fas fa-cloud-upload-alt text-5xl text-gray-300 mb-3"></i>
                <p class="text-gray-500 font-medium">Klik atau drag & drop file Excel template di sini</p>
                <p class="text-gray-400 text-xs mt-1">Format: .xlsx atau .xls • Maksimal 10MB</p>
                <input type="file" id="fileExcel" accept=".xlsx,.xls" class="hidden"
                       onchange="onFileSelected(this.files[0])">
            </div>

            {{-- File info bar --}}
            <div id="fileInfoBar"
                 class="hidden mt-3 flex items-center gap-3 px-4 py-2.5 bg-gray-50
                        border border-gray-200 rounded-lg text-sm">
                <i class="fas fa-file-excel text-green-500 text-lg"></i>
                <div class="flex-1 min-w-0">
                    <p id="fileInfoName" class="text-gray-700 font-medium truncate"></p>
                    <p id="fileInfoSize" class="text-gray-400 text-xs"></p>
                </div>
                <button onclick="resetUpload()"
                        class="text-xs text-red-400 hover:text-red-600 transition-colors flex items-center gap-1">
                    <i class="fas fa-times"></i> Ganti File
                </button>
            </div>

            {{-- Loading state --}}
            <div id="loadingBar" class="hidden mt-4">
                <div class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm"
                     style="background:#f0f9ff; border:1px solid #bae6fd; color:#0369a1;">
                    <svg class="animate-spin h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span id="loadingText">Membaca dan memvalidasi file Excel di server…</span>
                </div>
                <div class="mt-2 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                    <div id="loadingBar_inner"
                         class="h-full rounded-full animate-pulse"
                         style="width:100%; background:#38bdf8;"></div>
                </div>
            </div>

            {{-- ── PREVIEW RESULT (diisi oleh JS setelah AJAX) ── --}}
            <div id="previewSection" class="hidden mt-6 space-y-5">

                {{-- Statistik ringkasan --}}
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3" id="statsGrid"></div>

                {{-- Alert: periode tidak ditemukan di tabel time --}}
                <div id="timeNotFoundAlert" class="hidden rounded-lg p-4 text-sm"
                     style="background:#fef2f2; border:1px solid #fecaca; color:#b91c1c;">
                    <p class="font-semibold flex items-center gap-2 mb-1">
                        <i class="fas fa-exclamation-circle"></i>
                        Kolom Periode Tidak Ditemukan di Tabel Time
                    </p>
                    <p id="timeNotFoundDetail" class="text-xs"></p>
                    <p class="text-xs mt-1">
                        Pastikan tabel <code>time</code> sudah berisi data untuk tahun/periode tersebut.
                    </p>
                </div>

                {{-- Error baris --}}
                <div id="errorSection" class="hidden">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="w-2 h-2 rounded-full bg-red-400"></span>
                        <p class="text-sm font-semibold text-red-700">Baris Bermasalah</p>
                    </div>
                    <div class="border border-red-200 rounded-lg overflow-hidden text-xs">
                        <table class="w-full">
                            <thead class="bg-red-50 text-red-600">
                                <tr>
                                    <th class="px-3 py-2 text-left w-20">Baris Excel</th>
                                    <th class="px-3 py-2 text-left">Keterangan Masalah</th>
                                </tr>
                            </thead>
                            <tbody id="errorBody" class="divide-y divide-red-100"></tbody>
                        </table>
                    </div>
                </div>

                {{-- Duplikat --}}
                <div id="dupSection" class="hidden">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                            <p class="text-sm font-semibold text-amber-700">
                                Data Sudah Ada di Database
                            </p>
                        </div>
                        <label class="flex items-center gap-2 text-xs text-gray-600 cursor-pointer select-none">
                            <input type="checkbox" id="cbSkipDup" checked
                                   class="rounded border-gray-300 text-sky-500 focus:ring-sky-400">
                            Lewati duplikat saat import
                        </label>
                    </div>
                    <div class="border border-amber-200 rounded-lg overflow-hidden text-xs">
                        <table class="w-full">
                            <thead class="bg-amber-50 text-amber-700">
                                <tr>
                                    <th class="px-3 py-2 text-left">Metadata</th>
                                    <th class="px-3 py-2 text-left">Lokasi</th>
                                    <th class="px-3 py-2 text-left">Periode</th>
                                    <th class="px-3 py-2 text-right">Nilai</th>
                                </tr>
                            </thead>
                            <tbody id="dupBody" class="divide-y divide-amber-100"></tbody>
                        </table>
                    </div>
                </div>

                {{-- Data valid (preview max 20 record) --}}
                <div id="validSection" class="hidden">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="w-2 h-2 rounded-full bg-green-400"></span>
                        <p class="text-sm font-semibold text-green-700">
                            Data Valid — Siap Diimport
                        </p>
                    </div>
                    <div class="border border-green-200 rounded-lg overflow-x-auto max-h-72">
                        <table class="w-full text-xs">
                            <thead class="bg-green-50 text-green-700 sticky top-0">
                                <tr>
                                    <th class="px-3 py-2 text-left">Metadata</th>
                                    <th class="px-3 py-2 text-left">Lokasi</th>
                                    <th class="px-3 py-2 text-left">Periode</th>
                                    <th class="px-3 py-2 text-right">Nilai</th>
                                </tr>
                            </thead>
                            <tbody id="validBody" class="divide-y divide-green-100"></tbody>
                        </table>
                    </div>
                    <p id="validMore" class="hidden text-xs text-gray-400 text-right mt-1"></p>
                </div>

                {{-- Tombol Import --}}
                <div class="flex items-center justify-between pt-2 border-t border-gray-100">
                    <button onclick="resetUpload()"
                            class="text-sm text-gray-500 hover:text-gray-700 transition-colors
                                   flex items-center gap-1.5">
                        <i class="fas fa-arrow-left"></i> Ganti File
                    </button>

                    <button id="btnImport" onclick="doImport()" disabled
                            class="flex items-center gap-2 px-6 py-2.5 rounded-md text-sm font-semibold
                                   text-white shadow transition-colors
                                   disabled:bg-gray-300 disabled:cursor-not-allowed"
                            style="background:#0284c7;"
                            onmouseover="if(!this.disabled) this.style.background='#0369a1'"
                            onmouseout="if(!this.disabled) this.style.background='#0284c7'">
                        <i class="fas fa-file-import"></i>
                        <span id="btnImportText">Import Data</span>
                    </button>
                </div>

            </div>

            {{-- Import sedang berjalan --}}
            <div id="importingBar" class="hidden mt-5">
                <div class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm"
                     style="background:#f0fdf4; border:1px solid #bbf7d0; color:#166534;">
                    <svg class="animate-spin h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    Menyimpan data ke database, mohon tunggu…
                </div>
            </div>

            {{-- Hasil import --}}
            <div id="importResult" class="hidden mt-4"></div>

        </div>{{-- end panel-excel --}}

    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════
     JAVASCRIPT
══════════════════════════════════════════════════════════════ --}}
<script>
const CSRF         = '{{ csrf_token() }}';
const PREVIEW_URL  = '{{ route("data.preview_excel") }}';
const IMPORT_URL   = '{{ route("data.import_excel") }}';

/* ─────────────────────────────────────────────────────────────
   STATE
───────────────────────────────────────────────────────────── */
let currentFile  = null;   // File object terpilih
let previewData  = null;   // JSON dari server (hasil preview)

/* ─────────────────────────────────────────────────────────────
   TAB SWITCHER
───────────────────────────────────────────────────────────── */
function switchTab(tab) {
    document.getElementById('panel-manual').classList.toggle('hidden', tab !== 'manual');
    document.getElementById('panel-excel').classList.toggle('hidden', tab !== 'excel');

    const activeClass   = 'border-sky-500 text-sky-600';
    const inactiveClass = 'border-transparent text-gray-400 hover:text-gray-600';
    document.getElementById('tab-manual').className =
        `tab-btn px-5 py-2.5 text-sm font-semibold border-b-2 transition-colors ${tab === 'manual' ? activeClass : inactiveClass}`;
    document.getElementById('tab-excel').className =
        `tab-btn px-5 py-2.5 text-sm font-semibold border-b-2 transition-colors ${tab === 'excel' ? activeClass : inactiveClass}`;
}

/* ─────────────────────────────────────────────────────────────
   METADATA INFO (tab manual)
───────────────────────────────────────────────────────────── */
function updateMetadataInfo(select) {
    const opt    = select.options[select.selectedIndex];
    const info   = document.getElementById('metadataInfo');
    if (opt.dataset.tipe || opt.dataset.satuan) {
        document.getElementById('metadataTipe').textContent   = 'Tipe: ' + (opt.dataset.tipe || '-');
        document.getElementById('metadataSatuan').textContent = opt.dataset.satuan || '-';
        document.getElementById('satuanLabel').textContent    = opt.dataset.satuan ? `(${opt.dataset.satuan})` : '';
        info.classList.remove('hidden');
    } else {
        info.classList.add('hidden');
    }
}

/* ─────────────────────────────────────────────────────────────
   FILTER WAKTU (tab manual)
───────────────────────────────────────────────────────────── */
function filterWaktu() {
    const tahun = document.getElementById('filterTahun').value;
    const bulan = document.getElementById('filterBulan').value;
    document.querySelectorAll('#selectHari option[data-year]').forEach(opt => {
        const ok = (!tahun || opt.dataset.year === tahun)
                && (!bulan || opt.dataset.month === bulan);
        opt.style.display = ok ? '' : 'none';
    });
    const sel = document.getElementById('selectHari');
    if (sel.selectedOptions[0]?.style.display === 'none') sel.value = '';
}

/* ─────────────────────────────────────────────────────────────
   FILE SELECTION & DRAG-DROP
───────────────────────────────────────────────────────────── */
function handleDrop(e) {
    e.preventDefault();
    const zone = document.getElementById('dropZone');
    zone.style.borderColor = '';
    zone.style.background  = '';
    const file = e.dataTransfer.files[0];
    if (file) onFileSelected(file);
}

function onFileSelected(file) {
    // Validasi tipe & ukuran
    if (!file.name.match(/\.(xlsx|xls)$/i)) {
        showImportAlert('error', 'File harus berformat .xlsx atau .xls');
        return;
    }
    if (file.size > 10 * 1024 * 1024) {
        showImportAlert('error', 'Ukuran file maksimal 10MB');
        return;
    }

    currentFile = file;
    previewData = null;

    // Tampilkan info file
    document.getElementById('dropZone').classList.add('hidden');
    const bar = document.getElementById('fileInfoBar');
    bar.classList.remove('hidden');
    document.getElementById('fileInfoName').textContent = file.name;
    document.getElementById('fileInfoSize').textContent =
        file.size > 1048576
            ? (file.size / 1048576).toFixed(2) + ' MB'
            : (file.size / 1024).toFixed(1) + ' KB';

    // Langsung jalankan preview ke server
    doPreview();
}

function resetUpload() {
    currentFile = null;
    previewData = null;
    document.getElementById('fileExcel').value   = '';
    document.getElementById('dropZone').classList.remove('hidden');
    document.getElementById('fileInfoBar').classList.add('hidden');
    document.getElementById('loadingBar').classList.add('hidden');
    document.getElementById('previewSection').classList.add('hidden');
    document.getElementById('importingBar').classList.add('hidden');
    document.getElementById('importResult').classList.add('hidden');
}

/* ─────────────────────────────────────────────────────────────
   PREVIEW — AJAX ke DataController@previewExcel
   Server menggunakan DataImport::preview() untuk:
   • baca header baris 3
   • kenali jenis kolom periode (tahunan/quarter/semester/bulanan)
   • lookup time_id dari tabel `time`
   • deteksi duplikat di DB
   • kembalikan JSON { rows, errors, duplicates, period_cols, ... }
───────────────────────────────────────────────────────────── */
async function doPreview() {
    document.getElementById('loadingBar').classList.remove('hidden');
    document.getElementById('previewSection').classList.add('hidden');
    document.getElementById('importResult').classList.add('hidden');

    const form = new FormData();
    form.append('_token', CSRF);
    form.append('file_excel', currentFile);

    try {
        const resp = await fetch(PREVIEW_URL, { method: 'POST', body: form });
        const json = await resp.json();

        document.getElementById('loadingBar').classList.add('hidden');

        if (!json.success) {
            showImportAlert('error', json.message || 'Gagal membaca file.');
            resetUpload();
            return;
        }

        previewData = json;
        renderPreview(json);

    } catch (err) {
        document.getElementById('loadingBar').classList.add('hidden');
        showImportAlert('error', 'Terjadi kesalahan jaringan: ' + err.message);
        resetUpload();
    }
}

/* ─────────────────────────────────────────────────────────────
   RENDER PREVIEW
───────────────────────────────────────────────────────────── */
function renderPreview(json) {
    const section = document.getElementById('previewSection');
    section.classList.remove('hidden');

    // ── Statistik ──
    const periodLabel = {
        'tahunan' : 'Tahunan',
        'semester': 'Semester',
        'quarter' : 'Quarter',
        'bulanan' : 'Bulanan',
        'unknown' : '?',
    }[json.period_type] ?? json.period_type;

    document.getElementById('statsGrid').innerHTML = `
        <div class="rounded-lg p-3 text-center" style="background:#f0f9ff; border:1px solid #bae6fd;">
            <p class="text-xl font-bold" style="color:#0369a1;">${json.total_rows}</p>
            <p class="text-xs mt-0.5" style="color:#0369a1;">Baris Excel</p>
        </div>
        <div class="rounded-lg p-3 text-center" style="background:#f0fdf4; border:1px solid #bbf7d0;">
            <p class="text-xl font-bold" style="color:#166534;">${json.valid}</p>
            <p class="text-xs mt-0.5" style="color:#166534;">Record Valid</p>
        </div>
        <div class="rounded-lg p-3 text-center" style="background:#fffbeb; border:1px solid #fde68a;">
            <p class="text-xl font-bold" style="color:#92400e;">${json.duplicate}</p>
            <p class="text-xs mt-0.5" style="color:#92400e;">Duplikat</p>
        </div>
        <div class="rounded-lg p-3 text-center" style="background:#fef2f2; border:1px solid #fecaca;">
            <p class="text-xl font-bold" style="color:#b91c1c;">${json.error}</p>
            <p class="text-xs mt-0.5" style="color:#b91c1c;">Baris Error</p>
        </div>`;

    // ── Alert: kolom periode tidak ada di tabel time ──
    // Deteksi: semua errors yang mengandung "time_id tidak ditemukan"
    const timeErrors = (json.errors || []).filter(e => e.message && e.message.includes('time_id'));
    const timeNotFoundEl = document.getElementById('timeNotFoundAlert');
    if (timeErrors.length > 0) {
        const periods = [...new Set(timeErrors.map(e => e.period))].filter(Boolean);
        document.getElementById('timeNotFoundDetail').textContent =
            `Periode tidak terdaftar: ${periods.join(', ')}. ` +
            `Tipe periode terdeteksi: ${periodLabel}.`;
        timeNotFoundEl.classList.remove('hidden');
    } else {
        timeNotFoundEl.classList.add('hidden');
    }

    // ── Errors ──
    const errSection = document.getElementById('errorSection');
    const errBody    = document.getElementById('errorBody');
    if (json.errors && json.errors.length > 0) {
        errSection.classList.remove('hidden');
        errBody.innerHTML = json.errors.map(e => `
            <tr class="bg-red-50">
                <td class="px-3 py-2 text-red-500 font-mono">Baris ${esc(String(e.row))}</td>
                <td class="px-3 py-2 text-red-700">${esc(e.message)}</td>
            </tr>`).join('');
    } else {
        errSection.classList.add('hidden');
    }

    // ── Duplikat ──
    const dupSection = document.getElementById('dupSection');
    const dupBody    = document.getElementById('dupBody');
    if (json.duplicates && json.duplicates.length > 0) {
        dupSection.classList.remove('hidden');
        dupBody.innerHTML = json.duplicates.slice(0, 10).map(r => `
            <tr class="hover:bg-amber-50">
                <td class="px-3 py-2 text-gray-700">${esc(r.nama_metadata ?? String(r.metadata_id))}</td>
                <td class="px-3 py-2 text-gray-600">${esc(r.nama_lokasi  ?? String(r.location_id))}</td>
                <td class="px-3 py-2 text-gray-500">${esc(String(r.period_label))}</td>
                <td class="px-3 py-2 text-right font-mono text-gray-700">${formatNum(r.number_value)}</td>
            </tr>`).join('') +
            (json.duplicates.length > 10
                ? `<tr><td colspan="4" class="px-3 py-2 text-center text-amber-500 italic">
                    …dan ${json.duplicates.length - 10} duplikat lainnya
                   </td></tr>`
                : '');
    } else {
        dupSection.classList.add('hidden');
    }

    // ── Data valid ──
    const validSection = document.getElementById('validSection');
    const validBody    = document.getElementById('validBody');
    const validMore    = document.getElementById('validMore');
    if (json.rows && json.rows.length > 0) {
        validSection.classList.remove('hidden');
        const preview = json.rows.slice(0, 20);
        validBody.innerHTML = preview.map((r, i) => `
            <tr class="${i % 2 === 1 ? 'bg-green-50' : ''}">
                <td class="px-3 py-2 text-gray-700">${esc(r.nama_metadata ?? String(r.metadata_id))}</td>
                <td class="px-3 py-2 text-gray-600">${esc(r.nama_lokasi  ?? String(r.location_id))}</td>
                <td class="px-3 py-2">
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium"
                          style="background:#fef3c7; color:#b45309;">
                        ${esc(String(r.period_label))}
                    </span>
                </td>
                <td class="px-3 py-2 text-right font-mono text-gray-800 font-semibold">
                    ${formatNum(r.number_value)}
                </td>
            </tr>`).join('');

        if (json.rows.length > 20) {
            validMore.textContent = `Menampilkan 20 dari ${json.rows.length} record valid`;
            validMore.classList.remove('hidden');
        } else {
            validMore.classList.add('hidden');
        }
    } else {
        validSection.classList.add('hidden');
    }

    // ── Tombol import ──
    const btn = document.getElementById('btnImport');
    if (json.valid > 0) {
        btn.disabled = false;
        document.getElementById('btnImportText').textContent =
            `Import ${json.valid.toLocaleString('id-ID')} Record`;
    } else {
        btn.disabled = true;
        document.getElementById('btnImportText').textContent = 'Tidak Ada Data Valid';
    }
}

/* ─────────────────────────────────────────────────────────────
   IMPORT — kirim file ke DataController@importExcel
   Server menggunakan DataImport::import() dengan bulk insert
───────────────────────────────────────────────────────────── */
async function doImport() {
    if (!currentFile || !previewData) return;

    const skipDup = document.getElementById('cbSkipDup')?.checked ?? true;
    const btn     = document.getElementById('btnImport');

    // Konfirmasi
    const msg = previewData.valid > 0
        ? `Import ${previewData.valid} record data?\n` +
          (skipDup && previewData.duplicate > 0 ? `${previewData.duplicate} duplikat akan dilewati.` : '')
        : 'Tidak ada data valid untuk diimport.';

    if (!confirm(msg)) return;

    // UI loading
    btn.disabled = true;
    document.getElementById('importingBar').classList.remove('hidden');
    document.getElementById('previewSection').classList.add('hidden');

    const form = new FormData();
    form.append('_token',         CSRF);
    form.append('file_excel',     currentFile);
    form.append('skip_duplicates', skipDup ? '1' : '0');

    try {
        const resp = await fetch(IMPORT_URL, {
            method:  'POST',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body:    form,
        });
        const json = await resp.json();

        document.getElementById('importingBar').classList.add('hidden');

        if (json.success) {
            showImportAlert('success', json.message,
                json.redirect ? `<a href="${json.redirect}" class="underline font-semibold ml-2">Ke Halaman Data →</a>` : '');
            resetUpload();
        } else {
            showImportAlert('error', json.message || 'Import gagal.');
            // Tampilkan kembali preview agar user bisa coba lagi
            if (previewData) renderPreview(previewData);
        }

    } catch (err) {
        document.getElementById('importingBar').classList.add('hidden');
        showImportAlert('error', 'Terjadi kesalahan jaringan: ' + err.message);
        if (previewData) renderPreview(previewData);
    }
}

/* ─────────────────────────────────────────────────────────────
   HELPERS
───────────────────────────────────────────────────────────── */
function showImportAlert(type, msg, extra = '') {
    const isErr = type === 'error';
    const el    = document.getElementById('importResult');
    el.innerHTML = `
        <div class="flex items-start gap-3 px-4 py-3 rounded-lg text-sm"
             style="background:${isErr ? '#fef2f2' : '#f0fdf4'};
                    border:1px solid ${isErr ? '#fecaca' : '#bbf7d0'};
                    color:${isErr ? '#b91c1c' : '#166534'};">
            <i class="fas ${isErr ? 'fa-exclamation-circle text-red-400' : 'fa-check-circle text-green-500'} mt-0.5 shrink-0"></i>
            <span>${esc(msg)}${extra}</span>
        </div>`;
    el.classList.remove('hidden');
}

function esc(str) {
    if (str == null) return '-';
    return String(str)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

function formatNum(val) {
    if (val == null || val === '') return '-';
    const n = parseFloat(val);
    if (isNaN(n)) return esc(String(val));
    // Format angka Indonesia: titik ribuan, koma desimal
    return n % 1 === 0
        ? n.toLocaleString('id-ID')
        : n.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// Aktifkan tab yang benar saat load (jika ada error validation dari server)
@if($errors->any() || session('duplicate_warning'))
    switchTab('manual');
@endif
</script>
@endsection