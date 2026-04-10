@extends('layouts.main')

@section('content')
<div class="py-6">

    <a href="{{ route('data.index') }}"
       class="flex items-center gap-1 font-semibold text-sky-600 ps-4 mb-4 hover:text-sky-900 text-sm transition-colors">
        <i class="fas fa-angle-left"></i> Kembali
    </a>

    <div class="mt-2 bg-white rounded-xl shadow p-6">

        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-xl font-bold text-gray-800 mb-1">Input Data</h1>
                <p class="text-sm text-gray-400 mb-6">Data akan menunggu verifikasi admin sebelum ditampilkan</p>
            </div>
            <div class="text-right text-sm text-gray-500">
                <p id="current-date"></p>
                <p id="current-time" class="font-mono text-sky-600 font-semibold"></p>
            </div>
        </div>

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

        {{-- TAB 1: INPUT MANUAL --}}
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
                                    data-frekuensi="{{ $meta->frekuensi_penerbitan }}"
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
                                    {{ $loc->nama_wilayah }}
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

                        <input type="hidden" name="time_id" id="selectedTimeId" value="{{ old('time_id') }}">

                        <div class="flex gap-2">
                            {{-- DEKADE --}}
                            <div class="w-1/3">
                                <select id="filterDekade"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm
                                        focus:outline-none focus:ring-2 focus:ring-sky-400 bg-white
                                        disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed"
                                    onchange="filterWaktu()"
                                    disabled>
                                    <option value="">Dekade</option>
                                    @foreach($timeList->pluck('decade')->unique()->sortDesc() as $decade)
                                        <option value="{{ $decade }}">{{ $decade }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-xs text-gray-400" id="hintDekade"></p>
                            </div>

                            {{-- TAHUN --}}
                            <div class="w-1/3">
                                <select id="filterTahun"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm
                                        focus:outline-none focus:ring-2 focus:ring-sky-400 bg-white
                                        disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed"
                                    onchange="filterWaktu()"
                                    disabled>
                                    <option value="">Tahun</option>
                                    @foreach($timeList->pluck('year')->unique()->sortDesc() as $yr)
                                        <option value="{{ $yr }}">{{ $yr }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-xs text-gray-400" id="hintTahun"></p>
                            </div>

                            {{-- BULAN --}}
                            <div class="w-1/3">
                                <select id="filterBulan"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm
                                        focus:outline-none focus:ring-2 focus:ring-sky-400 bg-white
                                        disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed"
                                    onchange="filterWaktu()"
                                    disabled>
                                    <option value="">Bulan</option>
                                    @foreach(['Januari','Februari','Maret','April','Mei','Juni',
                                            'Juli','Agustus','September','Oktober','November','Desember']
                                            as $i => $bulan)
                                        <option value="{{ $i + 1 }}">{{ $bulan }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-xs text-gray-400" id="hintBulan"></p>
                            </div>
                        </div>

                        <div id="waktuInfo" class="hidden mt-2 px-3 py-2 bg-green-50 border border-green-200
                                                rounded-md text-xs text-green-700 flex items-center gap-2">
                            <i class="fas fa-check-circle"></i>
                            <span id="waktuInfoText"></span>
                        </div>

                        <div id="waktuHint" class="mt-2 px-3 py-2 bg-amber-50 border border-amber-200
                                                    rounded-md text-xs text-amber-700 flex items-center gap-2">
                            <i class="fas fa-info-circle"></i>
                            Pilih Metadata terlebih dahulu untuk mengaktifkan pilihan waktu.
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
                            placeholder="Contoh: 110.50"
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

        {{-- TAB 2: UPLOAD EXCEL --}}
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
                    <strong> Metadata → Export Template</strong>
                    dengan struktur kolom:
                </p>
                <div class="flex flex-wrap gap-1.5 mb-3">
                    @foreach(['metadata_id','nama_metadata','location_id','nama_wilayah'] as $col)
                        <code class="px-2 py-0.5 rounded text-xs font-mono font-bold"
                              style="background:#e0f2fe; color:#0369a1;">{{ $col }}</code>
                    @endforeach
                    <code class="px-2 py-0.5 rounded text-xs font-mono"
                          style="background:#fef3c7; color:#92400e;">{{ date('Y') }}</code>
                    <code class="px-2 py-0.5 rounded text-xs font-mono"
                          style="background:#fef3c7; color:#92400e;">{{ date('Y') + 1}}</code>
                    <code class="px-2 py-0.5 rounded text-xs font-mono"
                          style="background:#fef3c7; color:#92400e;">… dst</code>
                </div>
                <p class="text-xs text-gray-500">
                    Format kolom periode yang didukung:
                    <code class="bg-gray-100 px-1 rounded">{{ date('Y') }}</code> (Tahunan) ·
                    <code class="bg-gray-100 px-1 rounded">{{ date('Y') }}_Q1</code> (Quarter) ·
                    <code class="bg-gray-100 px-1 rounded">{{ date('Y') }}_S1</code> (Semester) ·
                    <code class="bg-gray-100 px-1 rounded">Jan_{{ date('Y') }}</code> (Bulanan)
                </p>
            </div>

            {{-- Drop Zone --}}
            <div id="dropZone"
                 class="border-2 border-dashed border-gray-300 rounded-xl p-10 text-center
                        transition-colors cursor-pointer"
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
                    <div class="h-full rounded-full animate-pulse"
                         style="width:100%; background:#38bdf8;"></div>
                </div>
            </div>

            {{-- PREVIEW RESULT --}}
            <div id="previewSection" class="hidden mt-6 space-y-4">

                {{-- Statistik ringkasan --}}
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-3" id="statsGrid"></div>

                {{-- ════════════════════════════════════════════════
                     WARNING: Metadata tidak ditemukan / tidak aktif
                ════════════════════════════════════════════════ --}}
                <div id="invalidMetaSection" class="hidden rounded-xl overflow-hidden"
                     style="border: 1px solid #c084fc;">
                    {{-- Header --}}
                    <div class="flex items-center gap-2.5 px-4 py-3 cursor-pointer select-none"
                         style="background: #faf5ff;"
                         onclick="toggleSection('meta')">
                        {{-- Ikon warning --}}
                        <div class="flex items-center justify-center w-7 h-7 rounded-full shrink-0"
                             style="background:#ede9fe;">
                            <svg class="w-4 h-4" style="color:#7c3aed;" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                      d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z"
                                      clip-rule="evenodd"/>
                            </svg>
                        </div>

                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold" style="color:#6d28d9;">
                                Metadata Tidak Valid — Data Tidak Akan Diimport
                            </p>
                            <p class="text-xs mt-0.5" style="color:#7c3aed;" id="invalidMetaSubtitle"></p>
                        </div>

                        <span id="metaBadge"
                              class="text-xs font-semibold px-2.5 py-1 rounded-full shrink-0"
                              style="background:#ede9fe; color:#6d28d9; border:1px solid #c084fc;"></span>

                        <svg id="metaChevron"
                             class="w-4 h-4 shrink-0 transition-transform duration-200"
                             style="color:#a78bfa;"
                             viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M4 6l4 4 4-4"/>
                        </svg>
                    </div>

                    {{-- Body (collapsible) --}}
                    <div id="metaBody" class="hidden" style="border-top:1px solid #e9d5ff;">
                        {{-- Tip --}}
                        <div class="px-4 py-2.5 text-xs flex items-start gap-2"
                             style="background:#fdf4ff; color:#86198f;">
                            <i class="fas fa-lightbulb mt-0.5 shrink-0" style="color:#c026d3;"></i>
                            <span>
                                Data dari metadata berikut <strong>dilewati sepenuhnya</strong>.
                                Pastikan metadata sudah terdaftar di sistem dan berstatus
                                <strong>Active</strong> sebelum mengimpor.
                            </span>
                        </div>

                        {{-- Tabel --}}
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs">
                                <thead style="background:#f3e8ff;">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-semibold w-24"
                                            style="color:#7c3aed;">ID</th>
                                        <th class="px-3 py-2 text-left font-semibold"
                                            style="color:#7c3aed;">Nama Metadata (dari Excel)</th>
                                        <th class="px-3 py-2 text-left font-semibold"
                                            style="color:#7c3aed;">Keterangan</th>
                                        <th class="px-3 py-2 text-left font-semibold"
                                            style="color:#7c3aed;">Baris</th>
                                    </tr>
                                </thead>
                                <tbody id="metaTableBody"
                                       class="divide-y" style="divide-color:#f3e8ff;"></tbody>
                            </table>
                        </div>

                        {{-- Show more --}}
                        <button id="metaShowMore"
                                class="hidden w-full flex items-center justify-center gap-1.5 py-2 text-xs
                                       transition-colors"
                                style="color:#7c3aed; border-top:1px solid #e9d5ff;"
                                onmouseover="this.style.background='#faf5ff'"
                                onmouseout="this.style.background=''"
                                onclick="showMore('meta')">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 16 16" fill="none"
                                 stroke="currentColor" stroke-width="1.5"><path d="M4 6l4 4 4-4"/></svg>
                            <span id="metaShowMoreTxt"></span>
                        </button>
                    </div>
                </div>
                {{-- END: Warning metadata --}}

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
                <div id="errorSection" class="hidden rounded-xl overflow-hidden border border-red-200">
                    <div class="flex items-center gap-2.5 px-4 py-2.5 bg-red-50 cursor-pointer select-none"
                         onclick="toggleSection('err')">
                        <span class="w-2 h-2 rounded-full bg-red-400 shrink-0"></span>
                        <p class="text-sm font-semibold text-red-700 flex-1">Terdapat baris bermasalah</p>
                        <span id="errBadge"
                              class="text-xs font-medium px-2 py-0.5 rounded-full
                                     bg-red-100 text-red-600 border border-red-200"></span>
                        <svg id="errChevron" class="w-4 h-4 text-red-400 transition-transform duration-200"
                             viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M4 6l4 4 4-4"/>
                        </svg>
                    </div>
                    <div id="errBody" class="hidden border-t border-red-200">
                        <table class="w-full text-xs">
                            <thead class="bg-red-50 text-red-600">
                                <tr>
                                    <th class="px-3 py-2 text-left font-medium w-28">Baris Excel</th>
                                    <th class="px-3 py-2 text-left font-medium">Keterangan masalah</th>
                                </tr>
                            </thead>
                            <tbody id="errTableBody" class="divide-y divide-red-100"></tbody>
                        </table>
                        <button id="errShowMore"
                                class="hidden w-full flex items-center justify-center gap-1.5 py-2 text-xs
                                       text-red-500 border-t border-red-100 hover:bg-red-50 transition-colors"
                                onclick="showMore('err')">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 16 16" fill="none"
                                 stroke="currentColor" stroke-width="1.5"><path d="M4 6l4 4 4-4"/></svg>
                            <span id="errShowMoreTxt"></span>
                        </button>
                    </div>
                </div>

                {{-- Duplikat --}}
                <div id="dupSection" class="hidden rounded-xl overflow-hidden border border-amber-200">
                    <div class="flex items-center gap-2.5 px-4 py-2.5 bg-amber-50 cursor-pointer select-none"
                         onclick="toggleSection('dup')">
                        <span class="w-2 h-2 rounded-full bg-amber-400 shrink-0"></span>
                        <p class="text-sm font-semibold text-amber-700 flex-1">Data sudah ada di database</p>
                        <span id="dupBadge"
                              class="text-xs font-medium px-2 py-0.5 rounded-full
                                     bg-amber-100 text-amber-600 border border-amber-200"></span>
                        <label class="flex items-center gap-1.5 text-xs text-amber-600 cursor-pointer ml-1"
                               onclick="event.stopPropagation()">
                            <input type="checkbox" id="cbSkipDup" checked
                                   class="rounded border-amber-300 text-amber-500 focus:ring-amber-400">
                            Lewati duplikat
                        </label>
                        <svg id="dupChevron" class="w-4 h-4 text-amber-400 transition-transform duration-200 ml-1"
                             viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M4 6l4 4 4-4"/>
                        </svg>
                    </div>
                    <div id="dupBody" class="hidden border-t border-amber-200">
                        <table class="w-full text-xs">
                            <thead class="bg-amber-50 text-amber-700">
                                <tr>
                                    <th class="px-3 py-2 text-left font-medium">Metadata</th>
                                    <th class="px-3 py-2 text-left font-medium">Lokasi</th>
                                    <th class="px-3 py-2 text-left font-medium">Periode</th>
                                    <th class="px-3 py-2 text-right font-medium">Nilai</th>
                                </tr>
                            </thead>
                            <tbody id="dupTableBody" class="divide-y divide-amber-100"></tbody>
                        </table>
                        <button id="dupShowMore"
                                class="hidden w-full flex items-center justify-center gap-1.5 py-2 text-xs
                                       text-amber-500 border-t border-amber-100 hover:bg-amber-50 transition-colors"
                                onclick="showMore('dup')">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 16 16" fill="none"
                                 stroke="currentColor" stroke-width="1.5"><path d="M4 6l4 4 4-4"/></svg>
                            <span id="dupShowMoreTxt"></span>
                        </button>
                    </div>
                </div>

                {{-- Data valid (preview max 20 record) --}}
                <div id="validSection" class="hidden">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="w-2 h-2 rounded-full bg-green-400"></span>
                        <p class="text-sm font-semibold text-green-700">Data Valid — Siap Diimport</p>
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
                            bg-sky-600 hover:bg-sky-700
                            disabled:bg-gray-200 disabled:text-gray-500 disabled:cursor-not-allowed">
                        <i class="fas fa-file-import"></i>
                        <span id="btnImportText">Import Data</span>
                    </button>
                </div>

            </div>{{-- end previewSection --}}

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
    const CSRF        = '{{ csrf_token() }}';
    const PREVIEW_URL = '{{ route("data.preview_excel") }}';
    const IMPORT_URL  = '{{ route("data.import_excel") }}';

    const TIME_LIST = @json($timeListJs);

    const FREKUENSI_RULES = {
        '10_tahunan': { dekade: true,  tahun: false, bulan: false },
        'tahunan':    { dekade: true,  tahun: true,  bulan: false },
        'bulanan':    { dekade: true,  tahun: true,  bulan: true  },
        'default':    { dekade: true,  tahun: true,  bulan: true  },
    };

    const FREKUENSI_LABELS = {
        '10_tahunan': { dekade: 'Wajib diisi', tahun: 'Tidak berlaku', bulan: 'Tidak berlaku' },
        'tahunan':    { dekade: 'Otomatis',    tahun: 'Wajib diisi',   bulan: 'Tidak berlaku' },
        'bulanan':    { dekade: 'Otomatis',    tahun: 'Wajib diisi',   bulan: 'Wajib diisi'   },
    };

    // ── State ─────────────────────────────────────────────────
    let currentFile = null;
    let previewData = null;

    const ROWS_PER_PAGE = 5;
    const sectionState  = {
        err:  { data: [], shown: 0 },
        dup:  { data: [], shown: 0 },
        meta: { data: [], shown: 0 },   // ← baru: invalid metadata
    };

    /* ─────────────────────────────────────────────────────────
    TAB SWITCHER
    ───────────────────────────────────────────────────────── */
    function switchTab(tab) {
        document.getElementById('panel-manual').classList.toggle('hidden', tab !== 'manual');
        document.getElementById('panel-excel').classList.toggle('hidden',  tab !== 'excel');

        const active   = 'border-sky-500 text-sky-600';
        const inactive = 'border-transparent text-gray-400 hover:text-gray-600';
        document.getElementById('tab-manual').className =
            `tab-btn px-5 py-2.5 text-sm font-semibold border-b-2 transition-colors ${tab === 'manual' ? active : inactive}`;
        document.getElementById('tab-excel').className =
            `tab-btn px-5 py-2.5 text-sm font-semibold border-b-2 transition-colors ${tab === 'excel' ? active : inactive}`;
    }

    /* ─────────────────────────────────────────────────────────
    METADATA INFO (tab manual)
    ───────────────────────────────────────────────────────── */
    function updateMetadataInfo(select) {
        const opt       = select.options[select.selectedIndex];
        const info      = document.getElementById('metadataInfo');
        const frekuensi = (opt.dataset.frekuensi || '').toLowerCase().trim();

        if (opt.dataset.tipe || opt.dataset.satuan) {
            document.getElementById('metadataTipe').textContent   = 'Tipe: ' + (opt.dataset.tipe || '-');
            document.getElementById('metadataSatuan').textContent = opt.dataset.satuan || '-';
            document.getElementById('satuanLabel').textContent    = opt.dataset.satuan ? `(${opt.dataset.satuan})` : '';
            info.classList.remove('hidden');
        } else {
            info.classList.add('hidden');
        }

        resetWaktuFields();

        if (!frekuensi) return;

        const rules  = FREKUENSI_RULES[frekuensi] || FREKUENSI_RULES['default'];
        const labels = FREKUENSI_LABELS[frekuensi] || {};

        setWaktuField('filterDekade', 'hintDekade', rules.dekade, labels.dekade);
        setWaktuField('filterTahun',  'hintTahun',  rules.tahun,  labels.tahun);
        setWaktuField('filterBulan',  'hintBulan',  rules.bulan,  labels.bulan);

        document.getElementById('waktuHint').classList.add('hidden');
    }

    /* ─────────────────────────────────────────────────────────
    FILTER WAKTU
    ───────────────────────────────────────────────────────── */
    function setWaktuField(selectId, hintId, enabled, hintText) {
        const sel  = document.getElementById(selectId);
        const hint = document.getElementById(hintId);
        sel.disabled = !enabled;
        sel.value    = '';
        enabled ? sel.classList.remove('opacity-50') : sel.classList.add('opacity-50');
        hint.textContent = hintText || '';
    }

    function resetWaktuFields() {
        ['filterDekade', 'filterTahun', 'filterBulan'].forEach(id => {
            const el = document.getElementById(id);
            el.disabled = true;
            el.value    = '';
            el.classList.add('opacity-50');
        });
        ['hintDekade', 'hintTahun', 'hintBulan'].forEach(id => {
            document.getElementById(id).textContent = '';
        });
        document.getElementById('selectedTimeId').value = '';
        document.getElementById('waktuInfo').classList.add('hidden');
        document.getElementById('waktuHint').classList.remove('hidden');
    }

    function filterWaktu() {
        const dekade = document.getElementById('filterDekade').value;
        const tahun  = document.getElementById('filterTahun').value;
        const bulan  = document.getElementById('filterBulan').value;

        const metaSel   = document.getElementById('metadataSelect');
        const frekuensi = metaSel.options[metaSel.selectedIndex]?.dataset?.frekuensi || '';
        const rules     = FREKUENSI_RULES[frekuensi] || FREKUENSI_RULES['default'];

        const dekadeOk = !rules.dekade || dekade !== '';
        const tahunOk  = !rules.tahun  || tahun  !== '';
        const bulanOk  = !rules.bulan  || bulan  !== '';

        if (!dekadeOk || !tahunOk || !bulanOk) {
            document.getElementById('selectedTimeId').value = '';
            document.getElementById('waktuInfo').classList.add('hidden');
            return;
        }

        const match = TIME_LIST.find(t => {
            const dekadeMatch = !rules.dekade || String(t.decade) === dekade;
            const tahunMatch  = !rules.tahun  || String(t.year)   === tahun;
            const bulanMatch  = !rules.bulan  || String(t.month)  === bulan;
            return dekadeMatch && tahunMatch && bulanMatch;
        });

        const infoEl   = document.getElementById('waktuInfo');
        const infoText = document.getElementById('waktuInfoText');

        if (match) {
            document.getElementById('selectedTimeId').value = match.time_id;
            let label = [];
            if (rules.dekade && dekade) label.push(`Dekade: ${dekade}`);
            if (rules.tahun  && tahun)  label.push(`Tahun: ${tahun}`);
            if (rules.bulan  && bulan) {
                const namaBulan = ['','Januari','Februari','Maret','April','Mei','Juni',
                                'Juli','Agustus','September','Oktober','November','Desember'];
                label.push(`Bulan: ${namaBulan[parseInt(bulan)]}`);
            }
            infoText.textContent = label.join(' · ') + ` (time_id: ${match.time_id})`;
            infoEl.classList.remove('hidden');
        } else {
            document.getElementById('selectedTimeId').value = '';
            infoText.textContent = 'Kombinasi waktu tidak ditemukan di database.';
            infoEl.classList.remove('hidden');
            infoEl.classList.replace('bg-green-50', 'bg-red-50');
            infoEl.classList.replace('border-green-200', 'border-red-200');
            infoEl.classList.replace('text-green-700', 'text-red-700');
        }
    }

    /* ─────────────────────────────────────────────────────────
    FILE SELECTION & DRAG-DROP
    ───────────────────────────────────────────────────────── */
    function handleDrop(e) {
        e.preventDefault();
        const zone = document.getElementById('dropZone');
        zone.style.borderColor = '';
        zone.style.background  = '';
        const file = e.dataTransfer.files[0];
        if (file) onFileSelected(file);
    }

    function onFileSelected(file) {
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

        document.getElementById('dropZone').classList.add('hidden');
        const bar = document.getElementById('fileInfoBar');
        bar.classList.remove('hidden');
        document.getElementById('fileInfoName').textContent = file.name;
        document.getElementById('fileInfoSize').textContent =
            file.size > 1048576
                ? (file.size / 1048576).toFixed(2) + ' MB'
                : (file.size / 1024).toFixed(1) + ' KB';

        doPreview();
    }

    function resetUpload() {
        currentFile = null;
        previewData = null;
        document.getElementById('fileExcel').value = '';
        document.getElementById('dropZone').classList.remove('hidden');
        document.getElementById('fileInfoBar').classList.add('hidden');
        document.getElementById('loadingBar').classList.add('hidden');
        document.getElementById('previewSection').classList.add('hidden');
        document.getElementById('importingBar').classList.add('hidden');
        sectionState.err  = { data: [], shown: 0 };
        sectionState.dup  = { data: [], shown: 0 };
        sectionState.meta = { data: [], shown: 0 };
    }

    async function doPreview() {
        document.getElementById('loadingBar').classList.remove('hidden');
        document.getElementById('previewSection').classList.add('hidden');
        document.getElementById('importResult').classList.add('hidden');

        const form = new FormData();
        form.append('_token', CSRF);
        form.append('file_excel', currentFile);

        try {
            const resp = await fetch(PREVIEW_URL, { method: 'POST', body: form });

            document.getElementById('loadingBar').classList.add('hidden');

            if (!resp.ok) {
                let errMsg = 'File ditolak server (status ' + resp.status + ').';
                try {
                    const errJson = await resp.json();
                    if (errJson.errors?.file_excel) {
                        errMsg = errJson.errors.file_excel[0];
                    } else if (errJson.message) {
                        errMsg = errJson.message;
                    }
                } catch (_) {}
                showImportAlertOnly(errMsg);
                return;
            }

            const json = await resp.json();

            if (!json.success) {
                showImportAlertOnly(json.message || 'Gagal membaca file.');
                return;
            }

            previewData = json;
            renderPreview(json);

        } catch (err) {
            document.getElementById('loadingBar').classList.add('hidden');
            showImportAlertOnly('Terjadi kesalahan jaringan: ' + err.message);
        }
    }

    function showImportAlertOnly(msg) {
        const el = document.getElementById('importResult');
        el.innerHTML = `
            <div class="flex items-start gap-3 px-4 py-3 rounded-lg text-sm"
                style="background:#fef2f2; border:1px solid #fecaca; color:#b91c1c;">
                <i class="fas fa-exclamation-circle text-red-400 mt-0.5 shrink-0"></i>
                <span>${esc(msg)}</span>
            </div>`;
        el.classList.remove('hidden');
    }

    /* ─────────────────────────────────────────────────────────
    RENDER PREVIEW
    ───────────────────────────────────────────────────────── */
    function renderPreview(json) {
        document.getElementById('previewSection').classList.remove('hidden');

        const periodLabel = {
            tahunan : 'Tahunan', semester: 'Semester',
            quarter : 'Quarter', bulanan : 'Bulanan', unknown: '?',
        }[json.period_type] ?? json.period_type;

        // ── Statistik (5 kotak, tambah invalid_meta) ──
        const invalidMetaCount = (json.invalid_metadata || []).length;
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
            </div>
            <div class="rounded-lg p-3 text-center" style="background:#faf5ff; border:1px solid #e9d5ff;">
                <p class="text-xl font-bold" style="color:#6d28d9;">${invalidMetaCount}</p>
                <p class="text-xs mt-0.5" style="color:#6d28d9;">Metadata Tidak Valid</p>
            </div>`;

        // ── Alert kolom periode tidak ada di tabel time ──
        const timeErrors     = (json.errors || []).filter(e => e.message?.includes('time_id'));
        const timeNotFoundEl = document.getElementById('timeNotFoundAlert');
        if (timeErrors.length > 0) {
            const periods = [...new Set(timeErrors.map(e => e.period))].filter(Boolean);
            document.getElementById('timeNotFoundDetail').textContent =
                `Periode tidak terdaftar: ${periods.join(', ')}. Tipe periode terdeteksi: ${periodLabel}.`;
            timeNotFoundEl.classList.remove('hidden');
        } else {
            timeNotFoundEl.classList.add('hidden');
        }

        // ── Isi sectionState lalu init semua sections ──
        initSections(json);

        // ── Data valid ──
        const validSection = document.getElementById('validSection');
        const validBody    = document.getElementById('validBody');
        const validMore    = document.getElementById('validMore');
        if (json.rows && json.rows.length > 0) {
            validSection.classList.remove('hidden');
            validBody.innerHTML = json.rows.slice(0, 20).map((r, i) => `
                <tr class="${i % 2 === 1 ? 'bg-green-50' : ''}">
                    <td class="px-3 py-2 text-gray-700">${esc(r.nama_metadata ?? String(r.metadata_id))}</td>
                    <td class="px-3 py-2 text-gray-600">${esc(r.nama_wilayah ?? String(r.location_id))}</td>
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

        const btn = document.getElementById('btnImport');
        const text = document.getElementById('btnImportText');

        if (json.valid > 0) {
            btn.disabled = false;

            // Aktif → warna biru
            btn.classList.remove('bg-gray-50', 'text-gray-400');
            btn.classList.add('bg-sky-600', 'hover:bg-sky-700', 'text-white');

            text.textContent = `Import ${json.valid.toLocaleString('id-ID')} Record`;
        } else {
            btn.disabled = true;

            // Tidak valid → warna abu terang
            btn.classList.remove('bg-sky-600', 'hover:bg-sky-700', 'text-white');
            btn.classList.add('bg-gray-50', 'text-gray-400');

            text.textContent = 'Tidak Ada Data Valid';
        }
    }

    /* ─────────────────────────────────────────────────────────
    COLLAPSE SECTIONS
    ───────────────────────────────────────────────────────── */
    function initSections(json) {
        sectionState.err  = { data: json.errors            || [], shown: 0 };
        sectionState.dup  = { data: json.duplicates        || [], shown: 0 };
        sectionState.meta = { data: json.invalid_metadata  || [], shown: 0 };

        // ── Error section ──
        const errSection = document.getElementById('errorSection');
        if (sectionState.err.data.length > 0) {
            document.getElementById('errBadge').textContent = sectionState.err.data.length + ' baris';
            errSection.classList.remove('hidden');
            document.getElementById('errBody').classList.add('hidden');
            document.getElementById('errChevron').style.transform = '';
        } else {
            errSection.classList.add('hidden');
        }

        // ── Duplicate section ──
        const dupSection = document.getElementById('dupSection');
        if (sectionState.dup.data.length > 0) {
            document.getElementById('dupBadge').textContent = sectionState.dup.data.length + ' entri';
            dupSection.classList.remove('hidden');
            document.getElementById('dupBody').classList.add('hidden');
            document.getElementById('dupChevron').style.transform = '';
        } else {
            dupSection.classList.add('hidden');
        }

        // ── Invalid metadata section ──
        const metaSection = document.getElementById('invalidMetaSection');
        const metaData    = sectionState.meta.data;
        if (metaData.length > 0) {
            document.getElementById('metaBadge').textContent = metaData.length + ' metadata';

            // Hitung berapa yang not_found vs not_active untuk subtitle
            const notFound  = metaData.filter(m => m.reason === 'not_found').length;
            const notActive = metaData.filter(m => m.reason === 'not_active').length;
            const parts     = [];
            if (notFound  > 0) parts.push(`${notFound} tidak ditemukan di sistem`);
            if (notActive > 0) parts.push(`${notActive} belum berstatus Active`);
            document.getElementById('invalidMetaSubtitle').textContent =
                parts.join(' · ') + ' — semua data dari metadata ini dilewati';

            metaSection.classList.remove('hidden');
            document.getElementById('metaBody').classList.add('hidden');
            document.getElementById('metaChevron').style.transform = '';
        } else {
            metaSection.classList.add('hidden');
        }
    }

    function toggleSection(type) {
        const ids = {
            err:  { body: 'errBody',  chevron: 'errChevron'  },
            dup:  { body: 'dupBody',  chevron: 'dupChevron'  },
            meta: { body: 'metaBody', chevron: 'metaChevron' },
        };
        const { body: bodyId, chevron: chevId } = ids[type];
        const body    = document.getElementById(bodyId);
        const chevron = document.getElementById(chevId);
        const isOpen  = !body.classList.contains('hidden');

        body.classList.toggle('hidden', isOpen);
        chevron.style.transform = isOpen ? '' : 'rotate(180deg)';

        if (!isOpen && sectionState[type].shown === 0) {
            sectionState[type].shown = ROWS_PER_PAGE;
            renderRows(type);
        }
    }

    function renderRows(type) {
        const s         = sectionState[type];
        const rows      = s.data.slice(0, s.shown);
        const remaining = s.data.length - s.shown;

        if (type === 'err') {
            document.getElementById('errTableBody').innerHTML = rows.map((e, i) => `
                <tr class="${i % 2 !== 0 ? 'bg-red-50' : ''}">
                    <td class="px-3 py-2 font-mono text-red-500">Baris ${esc(String(e.row))}</td>
                    <td class="px-3 py-2 text-red-700">${esc(e.message)}</td>
                </tr>`).join('');

            const btn = document.getElementById('errShowMore');
            if (remaining > 0) {
                btn.classList.remove('hidden');
                document.getElementById('errShowMoreTxt').textContent =
                    `Tampilkan ${Math.min(remaining, ROWS_PER_PAGE)} lagi (${remaining} tersisa)`;
            } else {
                btn.classList.add('hidden');
            }

        } else if (type === 'dup') {
            document.getElementById('dupTableBody').innerHTML = rows.map((r, i) => `
                <tr class="${i % 2 !== 0 ? 'bg-amber-50' : ''}">
                    <td class="px-3 py-2 text-gray-700">${esc(r.nama_metadata ?? String(r.metadata_id))}</td>
                    <td class="px-3 py-2 text-gray-500">${esc(r.nama_wilayah ?? String(r.location_id))}</td>
                    <td class="px-3 py-2 text-gray-500 font-mono">${esc(String(r.period_label))}</td>
                    <td class="px-3 py-2 text-right font-mono text-gray-700">${formatNum(r.number_value)}</td>
                </tr>`).join('');

            const btn = document.getElementById('dupShowMore');
            if (remaining > 0) {
                btn.classList.remove('hidden');
                document.getElementById('dupShowMoreTxt').textContent =
                    `Tampilkan ${Math.min(remaining, ROWS_PER_PAGE)} lagi (${remaining} tersisa)`;
            } else {
                btn.classList.add('hidden');
            }

        } else if (type === 'meta') {
            // ── Render tabel metadata tidak valid ──
            document.getElementById('metaTableBody').innerHTML = rows.map((m, i) => {
                const reasonBadge = m.reason === 'not_found'
                    ? `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium"
                               style="background:#fce7f3; color:#9d174d;">
                           <i class="fas fa-times-circle text-xs"></i> Metadata belum terdaftar di sistem
                       </span>`
                    : `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium"
                               style="background:#fef3c7; color:#92400e;">
                           <i class="fas fa-clock text-xs"></i> Status metadata ${esc(m.status_label ?? `tidak aktif`)}
                       </span>`;

                return `
                <tr class="${i % 2 !== 0 ? '' : ''}" style="background: ${i % 2 !== 0 ? '#fdf4ff' : '#ffffff'};">
                    <td class="px-3 py-2.5 font-mono font-semibold" style="color:#7c3aed;">
                        #${esc(String(m.metadata_id))}
                    </td>
                    <td class="px-3 py-2.5 text-gray-700 font-medium">
                        ${esc(m.nama_metadata ?? '-')}
                    </td>
                    <td class="px-3 py-2.5">${reasonBadge}</td>
                    <td class="px-3 py-2.5 font-mono text-gray-400">
                        Baris ${esc(String(m.row))}
                    </td>
                </tr>`;
            }).join('');

            const btn = document.getElementById('metaShowMore');
            if (remaining > 0) {
                btn.classList.remove('hidden');
                document.getElementById('metaShowMoreTxt').textContent =
                    `Tampilkan ${Math.min(remaining, ROWS_PER_PAGE)} lagi (${remaining} tersisa)`;
            } else {
                btn.classList.add('hidden');
            }
        }
    }

    function showMore(type) {
        sectionState[type].shown = Math.min(
            sectionState[type].shown + ROWS_PER_PAGE,
            sectionState[type].data.length
        );
        renderRows(type);
    }

    /* ─────────────────────────────────────────────────────────
    IMPORT
    ───────────────────────────────────────────────────────── */
    async function doImport() {
        if (!currentFile || !previewData) return;

        const skipDup = document.getElementById('cbSkipDup')?.checked ?? true;
        const btn     = document.getElementById('btnImport');

        const invalidCount = (previewData.invalid_metadata || []).length;
        let confirmMsg = `Import ${previewData.valid} record data?`;
        if (skipDup && previewData.duplicate > 0) {
            confirmMsg += `\n• ${previewData.duplicate} duplikat akan dilewati.`;
        }
        if (invalidCount > 0) {
            confirmMsg += `\n• ${invalidCount} metadata tidak valid — datanya tidak akan diimport.`;
        }

        if (!confirm(confirmMsg)) return;

        btn.disabled = true;
        document.getElementById('importingBar').classList.remove('hidden');
        document.getElementById('previewSection').classList.add('hidden');

        const form = new FormData();
        form.append('_token',          CSRF);
        form.append('file_excel',      currentFile);
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
                    json.redirect
                        ? `<a href="${json.redirect}" class="underline font-semibold ml-2">Ke Halaman Data →</a>`
                        : '');
                resetUpload();
            } else {
                showImportAlert('error', json.message || 'Import gagal.');
                if (previewData) renderPreview(previewData);
            }

        } catch (err) {
            document.getElementById('importingBar').classList.add('hidden');
            showImportAlert('error', 'Terjadi kesalahan jaringan: ' + err.message);
            if (previewData) renderPreview(previewData);
        }
    }

    /* ─────────────────────────────────────────────────────────
    HELPERS
    ───────────────────────────────────────────────────────── */
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
        return n % 1 === 0
            ? n.toLocaleString('id-ID')
            : n.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    @if($errors->any() || session('duplicate_warning'))
        switchTab('manual');
    @endif
</script>
@endsection