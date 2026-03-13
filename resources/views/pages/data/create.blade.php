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
                               class="text-xs bg-amber-100 hover:bg-amber-200 text-amber-700 px-3 py-1.5 rounded-md font-medium transition-colors">
                                <i class="fas fa-eye mr-1"></i> Lihat Data Existing
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- TAB SWITCHER --}}
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

        {{-- ═══════════════════════════════════════ --}}
        {{-- TAB 1: INPUT MANUAL                    --}}
        {{-- ═══════════════════════════════════════ --}}
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
                                   rounded-md px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-sky-400 bg-white"
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
                            <p class="mt-1 text-xs text-red-500"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                        @enderror

                        {{-- Info metadata yang dipilih --}}
                        <div id="metadataInfo" class="hidden mt-2 px-3 py-2 bg-sky-50 border border-sky-100 rounded-md text-xs text-sky-700">
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
                                   rounded-md px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-sky-400 bg-white">
                            <option value="">-- Pilih Lokasi --</option>
                            @foreach($locationList as $loc)
                                <option value="{{ $loc->location_id }}"
                                    {{ old('location_id') == $loc->location_id ? 'selected' : '' }}>
                                    {{ $loc->kabupaten }} — {{ $loc->kecamatan }}, {{ $loc->desa }}
                                </option>
                            @endforeach
                        </select>
                        @error('location_id')
                            <p class="mt-1 text-xs text-red-500"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Waktu --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                            Waktu <span class="text-red-500">*</span>
                        </label>
                        <div class="flex gap-2">
                            {{-- Pilih Tahun dulu --}}
                            <select id="filterTahun"
                                class="border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-sky-400 bg-white w-1/3"
                                onchange="filterWaktu()">
                                <option value="">Tahun</option>
                                @foreach($timeList->pluck('year')->unique()->sortDesc() as $yr)
                                    <option value="{{ $yr }}">{{ $yr }}</option>
                                @endforeach
                            </select>
                            {{-- Pilih Bulan --}}
                            <select id="filterBulan"
                                class="border border-gray-300 rounded-md px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-sky-400 bg-white w-1/3"
                                onchange="filterWaktu()">
                                <option value="">Bulan</option>
                                @foreach(['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'] as $i => $bulan)
                                    <option value="{{ $i + 1 }}">{{ $bulan }}</option>
                                @endforeach
                            </select>
                            {{-- Pilih Hari (time_id) --}}
                            <select name="time_id" id="selectHari" required
                                class="border @error('time_id') border-red-400 @else border-gray-300 @enderror
                                       rounded-md px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-sky-400 bg-white flex-1">
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
                            <p class="mt-1 text-xs text-red-500"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Nilai (number) --}}
                    <div id="fieldNumber">
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

                {{-- Nilai Teks --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Nilai Teks</label>
                    <textarea name="text_value" rows="2"
                        placeholder="Isi jika nilai berupa deskripsi atau teks..."
                        class="w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm
                               focus:outline-none focus:ring-2 focus:ring-sky-400 resize-none">{{ old('text_value') }}</textarea>
                </div>

                {{-- Kategori --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Nilai Kategori</label>
                    <input type="number" name="kategori_value" value="{{ old('kategori_value') }}"
                        placeholder="Isi jika nilai berupa kode kategori..."
                        class="w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm
                               focus:outline-none focus:ring-2 focus:ring-sky-400">
                </div>

                {{-- Keterangan lain --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Keterangan Lain</label>
                    <input type="text" name="other" value="{{ old('other') }}" maxlength="100"
                        placeholder="Keterangan tambahan (opsional)..."
                        class="w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm
                               focus:outline-none focus:ring-2 focus:ring-sky-400">
                </div>

                {{-- Analisis Fenomena --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Analisis Fenomena</label>
                    <textarea name="analisis_fenomena" rows="3"
                        placeholder="Tuliskan analisis atau catatan fenomena terkait data ini (opsional)..."
                        class="w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm
                               focus:outline-none focus:ring-2 focus:ring-sky-400 resize-none">{{ old('analisis_fenomena') }}</textarea>
                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit"
                        class="bg-sky-600 hover:bg-sky-700 text-white px-6 py-2.5 rounded-md shadow text-sm font-semibold
                               flex items-center gap-2 transition-colors">
                        <i class="fas fa-save"></i> Simpan Data
                    </button>
                </div>
            </form>
        </div>

        {{-- ═══════════════════════════════════════ --}}
        {{-- TAB 2: UPLOAD EXCEL                    --}}
        {{-- ═══════════════════════════════════════ --}}
        <div id="panel-excel" class="hidden">

            {{-- Info format --}}
            <div class="mb-5 bg-blue-50 border border-blue-200 rounded-lg p-4 text-sm text-gray-900">
                <p class="font-semibold flex items-center gap-2 mb-2 text-blue-6009">
                    <i class="fas fa-info-circle"></i> Format Excel yang Diperlukan
                </p>
                <p class="text-xs text-gray-900 mb-2">Pastikan file Excel memiliki kolom header berikut di baris pertama:</p>
                <div class="flex flex-wrap gap-2">
                    @foreach(['metadata_id','location_id','time_id','number_value','text_value','kategori_value','other','analisis_fenomena'] as $col)
                        <code class="bg-blue-100 text-gray-900 px-2 py-0.5 rounded text-xs font-mono">{{ $col }}</code>
                    @endforeach
                </div>
                <p class="text-xs text-gray-900 mt-2">
                    <strong>Wajib diisi:</strong> metadata_id, location_id, time_id.
                    Kolom lainnya boleh kosong.
                </p>
                <a href="{{ route('data.template_excel') }}"
                   class="mt-2 inline-flex items-center gap-1.5 text-xs text-blue-600 hover:text-blue-800 font-semibold underline">
                    <i class="fas fa-download"></i> Download Template Excel
                </a>
            </div>

            {{-- Upload form --}}
            <div id="uploadZone"
                 class="border-2 border-dashed border-gray-300 rounded-xl p-8 text-center hover:border-sky-400
                        hover:bg-sky-50 transition-colors cursor-pointer"
                 onclick="document.getElementById('fileExcel').click()"
                 ondragover="event.preventDefault(); this.classList.add('border-sky-400','bg-sky-50')"
                 ondragleave="this.classList.remove('border-sky-400','bg-sky-50')"
                 ondrop="handleDrop(event)">
                <i class="fas fa-file-excel text-4xl text-gray-300 mb-3"></i>
                <p class="text-gray-500 font-medium text-sm">Klik atau drag & drop file Excel di sini</p>
                <p class="text-gray-400 text-xs mt-1">Format: .xlsx atau .xls • Maksimal 5MB</p>
                <input type="file" id="fileExcel" accept=".xlsx,.xls" class="hidden" onchange="previewExcel(this)">
            </div>

            {{-- File terpilih --}}
            <div id="fileInfo" class="hidden mt-3 flex items-center gap-3 px-4 py-2.5 bg-gray-50 border rounded-lg text-sm">
                <i class="fas fa-file-excel text-green-500"></i>
                <span id="fileName" class="text-gray-700 font-medium flex-1"></span>
                <span id="fileSize" class="text-gray-400 text-xs"></span>
                <button onclick="clearFile()" class="text-red-400 hover:text-red-600 text-xs transition-colors">
                    <i class="fas fa-times"></i> Hapus
                </button>
            </div>

            {{-- Loading preview --}}
            <div id="loadingPreview" class="hidden mt-4 flex items-center gap-3 text-sky-600 text-sm">
                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                Membaca file Excel...
            </div>

            {{-- Preview result --}}
            <div id="previewResult" class="hidden mt-5">

                {{-- Summary --}}
                <div class="grid grid-cols-3 gap-3 mb-4" id="previewSummary"></div>

                {{-- Error rows --}}
                <div id="errorSection" class="hidden mb-4">
                    <p class="text-sm font-semibold text-red-700 mb-2 flex items-center gap-2">
                        <i class="fas fa-exclamation-circle text-red-500"></i>
                        Baris dengan Format Tidak Sesuai
                    </p>
                    <div class="border border-red-200 rounded-lg overflow-hidden text-xs">
                        <table class="w-full">
                            <thead class="bg-red-50 text-red-600">
                                <tr>
                                    <th class="px-3 py-2 text-left">Baris</th>
                                    <th class="px-3 py-2 text-left">Keterangan</th>
                                </tr>
                            </thead>
                            <tbody id="errorRows" class="divide-y divide-red-100"></tbody>
                        </table>
                    </div>
                </div>

                {{-- Duplicate rows --}}
                <div id="duplicateSection" class="hidden mb-4">
                    <p class="text-sm font-semibold text-amber-700 mb-2 flex items-center gap-2">
                        <i class="fas fa-copy text-amber-500"></i>
                        Baris Data Duplikat
                    </p>
                    <div class="border border-amber-200 rounded-lg overflow-hidden text-xs">
                        <table class="w-full">
                            <thead class="bg-amber-50 text-amber-600">
                                <tr>
                                    <th class="px-3 py-2 text-left">Baris</th>
                                    <th class="px-3 py-2 text-left">Keterangan</th>
                                </tr>
                            </thead>
                            <tbody id="duplicateRows" class="divide-y divide-amber-100"></tbody>
                        </table>
                    </div>
                    <label class="flex items-center gap-2 mt-2 text-xs text-gray-600 cursor-pointer">
                        <input type="checkbox" id="skipDuplicates" checked
                            class="rounded border-gray-300 text-sky-500 focus:ring-sky-400">
                        Lewati data duplikat (direkomendasikan)
                    </label>
                </div>

                {{-- Valid data preview --}}
                <div id="validSection" class="hidden mb-4">
                    <p class="text-sm font-semibold text-green-700 mb-2 flex items-center gap-2">
                        <i class="fas fa-check-circle text-green-500"></i>
                        Data Valid — Siap Diimport
                    </p>
                    <div class="border border-green-200 rounded-lg overflow-auto max-h-64 text-xs">
                        <table class="w-full">
                            <thead class="bg-green-50 text-green-700 sticky top-0">
                                <tr id="previewTableHead"></tr>
                            </thead>
                            <tbody id="previewTableBody" class="divide-y divide-green-100"></tbody>
                        </table>
                    </div>
                </div>

                {{-- Submit import --}}
                <form id="importForm" action="{{ route('data.import_excel') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="file" name="file_excel" id="hiddenFileInput" class="hidden">
                    <input type="hidden" name="skip_duplicates" id="skipDuplicatesInput" value="1">

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" onclick="clearFile()"
                            class="border border-gray-300 text-gray-500 hover:bg-gray-50 px-4 py-2 rounded-md text-sm transition-colors">
                            Batal
                        </button>
                        <button type="submit" id="btnImport" disabled
                            class="bg-sky-600 hover:bg-sky-700 disabled:bg-gray-300 disabled:cursor-not-allowed
                                   text-white px-6 py-2.5 rounded-md shadow text-sm font-semibold
                                   flex items-center gap-2 transition-colors">
                            <i class="fas fa-file-import"></i>
                            <span id="importBtnText">Import Data</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

<script>
    // ═══════════════════════════════════════════
    // TAB SWITCHER
    // ═══════════════════════════════════════════
    function switchTab(tab) {
        document.getElementById('panel-manual').classList.toggle('hidden', tab !== 'manual');
        document.getElementById('panel-excel').classList.toggle('hidden', tab !== 'excel');

        document.getElementById('tab-manual').className =
            'tab-btn px-5 py-2.5 text-sm font-semibold border-b-2 transition-colors ' +
            (tab === 'manual' ? 'border-sky-500 text-sky-600' : 'border-transparent text-gray-400 hover:text-gray-600');
        document.getElementById('tab-excel').className =
            'tab-btn px-5 py-2.5 text-sm font-semibold border-b-2 transition-colors ' +
            (tab === 'excel' ? 'border-sky-500 text-sky-600' : 'border-transparent text-gray-400 hover:text-gray-600');
    }

    // ═══════════════════════════════════════════
    // METADATA INFO
    // ═══════════════════════════════════════════
    function updateMetadataInfo(select) {
        const opt    = select.options[select.selectedIndex];
        const tipe   = opt.dataset.tipe;
        const satuan = opt.dataset.satuan;
        const info   = document.getElementById('metadataInfo');

        if (tipe || satuan) {
            document.getElementById('metadataTipe').textContent   = 'Tipe: ' + (tipe || '-');
            document.getElementById('metadataSatuan').textContent = satuan || '-';
            document.getElementById('satuanLabel').textContent    = satuan ? '(' + satuan + ')' : '';
            info.classList.remove('hidden');
        } else {
            info.classList.add('hidden');
        }
    }

    // ═══════════════════════════════════════════
    // FILTER WAKTU (Tahun → Bulan → Hari)
    // ═══════════════════════════════════════════
    function filterWaktu() {
        const tahun = document.getElementById('filterTahun').value;
        const bulan = document.getElementById('filterBulan').value;
        const selectHari = document.getElementById('selectHari');
        const allOptions = selectHari.querySelectorAll('option[data-year]');

        allOptions.forEach(opt => {
            const matchTahun = !tahun || opt.dataset.year === tahun;
            const matchBulan = !bulan || opt.dataset.month === bulan;
            opt.style.display = (matchTahun && matchBulan) ? '' : 'none';
        });

        // Reset hari jika tidak sesuai filter
        const selected = selectHari.options[selectHari.selectedIndex];
        if (selected && selected.style.display === 'none') {
            selectHari.value = '';
        }
    }

    // ═══════════════════════════════════════════
    // EXCEL PREVIEW
    // ═══════════════════════════════════════════
    function handleDrop(e) {
        e.preventDefault();
        document.getElementById('uploadZone').classList.remove('border-sky-400', 'bg-sky-50');
        const file = e.dataTransfer.files[0];
        if (file) processFile(file);
    }

    function previewExcel(input) {
        if (input.files[0]) processFile(input.files[0]);
    }

    function processFile(file) {
    const allowed = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                     'application/vnd.ms-excel'];
    if (!allowed.includes(file.type) && !file.name.match(/\.(xlsx|xls)$/i)) {
        alert('File harus berformat .xlsx atau .xls');
        return;
    }
    if (file.size > 5 * 1024 * 1024) {
        alert('Ukuran file maksimal 5MB');
        return;
    }

    document.getElementById('fileInfo').classList.remove('hidden');
    document.getElementById('fileName').textContent = file.name;
    document.getElementById('fileSize').textContent = (file.size / 1024).toFixed(1) + ' KB';
    document.getElementById('loadingPreview').classList.remove('hidden');
    document.getElementById('previewResult').classList.add('hidden');

    // ── Baca Excel di browser pakai SheetJS ──
    const reader = new FileReader();
    reader.onload = function(e) {
        try {
            const data     = new Uint8Array(e.target.result);
            const workbook = XLSX.read(data, { type: 'array' });
            const sheet    = workbook.Sheets[workbook.SheetNames[0]];
            const rows     = XLSX.utils.sheet_to_json(sheet, { defval: '' });

            document.getElementById('loadingPreview').classList.add('hidden');
            renderPreviewClient(rows, file);
        } catch (err) {
            document.getElementById('loadingPreview').classList.add('hidden');
            alert('Gagal membaca file Excel: ' + err.message);
        }
    };
    reader.readAsArrayBuffer(file);
}

function renderPreviewClient(rows, file) {
    if (rows.length === 0) {
        alert('File Excel kosong atau format tidak dikenali.');
        return;
    }

    const requiredCols = ['metadata_id', 'location_id', 'time_id'];

    const errors     = [];
    const duplicates = []; // cek duplikat tetap di server saat import
    const validRows  = [];

    rows.forEach((row, idx) => {
        const rowNum = idx + 2;
        // Validasi kolom wajib
        const missing = requiredCols.filter(c => !row[c] && row[c] !== 0);
        if (missing.length > 0) {
            errors.push({ row: rowNum, message: `Kolom wajib kosong: ${missing.join(', ')}` });
        } else {
            validRows.push(row);
        }
    });

    const result = document.getElementById('previewResult');
    result.classList.remove('hidden');

    // Summary
    document.getElementById('previewSummary').innerHTML = `
        <div class="bg-green-50 border border-green-200 rounded-lg p-3 text-center">
            <p class="text-2xl font-bold text-green-700">${validRows.length}</p>
            <p class="text-xs text-green-600 mt-0.5">Data Valid</p>
        </div>
        <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 text-center">
            <p class="text-2xl font-bold text-amber-700">-</p>
            <p class="text-xs text-amber-600 mt-0.5">Duplikat (dicek saat import)</p>
        </div>
        <div class="bg-red-50 border border-red-200 rounded-lg p-3 text-center">
            <p class="text-2xl font-bold text-red-700">${errors.length}</p>
            <p class="text-xs text-red-600 mt-0.5">Format Salah</p>
        </div>`;

    // Error rows
    if (errors.length > 0) {
        document.getElementById('errorSection').classList.remove('hidden');
        document.getElementById('errorRows').innerHTML = errors.map(e =>
            `<tr class="bg-red-50">
                <td class="px-3 py-2 text-red-600 font-mono">Baris ${e.row}</td>
                <td class="px-3 py-2 text-red-700">${e.message}</td>
            </tr>`
        ).join('');
    }

    // Valid rows preview (max 10 baris)
    if (validRows.length > 0) {
        document.getElementById('validSection').classList.remove('hidden');
        const cols = Object.keys(validRows[0]);

        document.getElementById('previewTableHead').innerHTML =
            cols.map(c => `<th class="px-3 py-2 text-left font-semibold">${c}</th>`).join('');

        document.getElementById('previewTableBody').innerHTML =
            validRows.slice(0, 10).map(row =>
                `<tr class="hover:bg-green-50">
                    ${cols.map(c => `<td class="px-3 py-2 text-gray-600">${row[c] ?? '-'}</td>`).join('')}
                </tr>`
            ).join('') +
            (validRows.length > 10
                ? `<tr><td colspan="${cols.length}" class="px-3 py-2 text-center text-gray-400 italic">
                    ...dan ${validRows.length - 10} baris lainnya
                   </td></tr>`
                : '');

        // Aktifkan tombol import
        document.getElementById('btnImport').disabled = false;
        document.getElementById('importBtnText').textContent = `Import ${validRows.length} Data`;

        // Set file ke hidden input untuk dikirim ke server
        const dt = new DataTransfer();
        dt.items.add(file);
        document.getElementById('hiddenFileInput').files = dt.files;
    }
}

    function clearFile() {
        document.getElementById('fileExcel').value   = '';
        document.getElementById('fileInfo').classList.add('hidden');
        document.getElementById('previewResult').classList.add('hidden');
        document.getElementById('loadingPreview').classList.add('hidden');
        document.getElementById('errorSection').classList.add('hidden');
        document.getElementById('duplicateSection').classList.add('hidden');
        document.getElementById('validSection').classList.add('hidden');
        document.getElementById('btnImport').disabled = true;
    }

    // Trigger old tab jika ada error
    @if($errors->any() || session('duplicate_warning'))
        switchTab('manual');
    @endif
</script>
@endsection