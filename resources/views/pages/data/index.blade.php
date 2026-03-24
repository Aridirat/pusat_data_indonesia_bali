@extends('layouts.main')

@section('content')

@php
    $activeMetadataId   = request('metadata_id', '');
    $activeKabupaten    = request('kabupaten', '');
    $activeKecamatan    = request('kecamatan', '');
    $activeDesa         = request('desa', '');
    $activeYear         = request('year', '');
    $activeSearch       = request('search', '');
    $activeTemplateId   = request('template_id', '');
    $activeMetadataNama = $metadataList->firstWhere('metadata_id', $activeMetadataId)?->nama ?? '';
@endphp

<div class="mt-2 bg-white rounded-xl shadow p-6">

    {{-- HEADER --}}
    <div class="flex justify-between items-start">
        <div>
            <h1 class="text-xl font-bold text-gray-800">Data</h1>
            <p class="text-sm text-gray-400 mt-1">Pilih filter untuk menampilkan data</p>
        </div>
        <div class="text-right text-sm text-gray-500">
            <p id="current-date"></p>
            <p id="current-time" class="font-mono text-sky-600 font-semibold"></p>
        </div>
    </div>

    {{-- ALERT --}}
    @if(session('success'))
        <div class="mt-4 flex items-center gap-3 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
            <i class="fas fa-check-circle text-green-500 shrink-0"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- ACTION BAR --}}
    <div class="flex flex-wrap justify-between items-center mt-5 gap-3">
        <div class="flex gap-2">
            <a href="{{ route('data.create') }}"
               class="px-4 py-2 bg-sky-500 hover:bg-sky-600 text-white text-sm font-semibold rounded-lg
                      shadow-md shadow-sky-400/30 flex items-center gap-2 transition-colors">
                <i class="fas fa-plus"></i> Input Data
            </a>
            @if(isset($pendingCount) && $pendingCount > 0)
                <a href="{{ route('data.approval') }}"
                   class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold rounded-lg
                          flex items-center gap-2 transition-colors">
                    <i class="fas fa-clock"></i> Approval
                    <span class="bg-white text-amber-600 text-xs font-bold px-1.5 py-0.5 rounded-full">
                        {{ $pendingCount }}
                    </span>
                </a>
            @endif
        </div>

        <div class="flex gap-2 items-center">
            {{-- Tombol Simpan Template — muncul saat ada minimal 1 filter aktif --}}
            <button id="btnSaveTemplate" onclick="openTemplateModal()"
                class="hidden px-4 py-2 text-sm font-semibold rounded-lg flex items-center gap-2 transition-colors shadow-md"
                style="background:#8b5cf6; color:#fff;"
                onmouseover="this.style.background='#7c3aed'"
                onmouseout="this.style.background='#8b5cf6'">
                <i class="fas fa-bookmark"></i> Simpan Template
            </button>

            {{-- Export Buttons --}}
            @if($hasFilter && request('metadata_id'))
                @include('pages.data._export_buttons')
            @endif
        </div>
    </div>

    {{-- TEMPLATE PILLS --}}
    @if($availableTemplates->count() > 0)
        <div class="mt-4 flex flex-wrap gap-2 items-center">
            <span class="text-xs text-gray-400 font-medium shrink-0">
                <i class="fas fa-bookmark mr-1"></i> Template saya:
            </span>
            @foreach($availableTemplates as $tmpl)
                <div class="flex items-center">
                    <a href="{{ route('data.index', ['template_id' => $tmpl->tampilan_id]) }}"
                       title="{{ $tmpl->filter_params ? collect($tmpl->filter_params)->filter()->map(fn($v,$k) => "$k: $v")->implode(' | ') : 'Tidak ada filter tersimpan' }}"
                       class="px-3 py-1 rounded-l-full text-xs font-medium border transition-colors
                           {{ request('template_id') == $tmpl->tampilan_id
                               ? 'bg-purple-500 text-white border-purple-500'
                               : 'bg-white text-gray-600 border-gray-300 hover:border-purple-400 hover:text-purple-600' }}">
                        {{ $tmpl->nama_tampilan }}
                    </a>
                    <form action="{{ route('data.template.delete', $tmpl->tampilan_id) }}"
                          method="POST"
                          onsubmit="return confirm('Hapus template \'{{ $tmpl->nama_tampilan }}\'?')">
                        @csrf @method('DELETE')
                        <button type="submit"
                            class="px-2 py-1 rounded-r-full text-xs border border-l-0 transition-colors
                               {{ request('template_id') == $tmpl->tampilan_id
                                   ? 'bg-purple-500 text-purple-200 border-purple-500 hover:text-white'
                                   : 'bg-white text-gray-400 border-gray-300 hover:text-red-500 hover:border-red-300' }}">
                            ×
                        </button>
                    </form>
                </div>
            @endforeach
            @if(request('template_id'))
                <a href="{{ route('data.index') }}"
                   class="px-3 py-1 rounded-full text-xs border border-gray-200 text-gray-400
                          hover:text-red-500 hover:border-red-300 transition-colors">
                    <i class="fas fa-times mr-1"></i> Reset
                </a>
            @endif
        </div>
    @endif

    {{-- ═══════════ FILTER ═══════════ --}}
    <form method="GET" id="filterForm" class="mt-5 space-y-3">
        @if(request('template_id'))
            <input type="hidden" name="template_id" value="{{ request('template_id') }}">
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">

            {{-- ── Metadata (Searchable + Show All on Focus) ── --}}
            <div>
                <label class="block text-xs text-gray-500 font-medium mb-1">
                    <i class="fas fa-database mr-1 text-gray-400"></i> Metadata
                </label>
                <div class="relative" id="metadataDropdownWrap">
                    <input type="text" id="metadataSearch"
                        placeholder="Klik atau ketik untuk mencari..."
                        autocomplete="off"
                        value="{{ $activeMetadataNama }}"
                        oninput="onMetadataInput()"
                        onfocus="onMetadataFocus()"
                        class="w-full border border-gray-300 rounded-md pl-8 pr-7 py-2 text-sm
                               focus:outline-none focus:ring-2 focus:ring-sky-400 bg-white cursor-pointer">
                    <i class="fas fa-chevron-down absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none" id="metadataChevron"></i>
                    <button type="button" id="clearMetadata" onclick="clearMetadataFilter()"
                        class="{{ $activeMetadataNama ? '' : 'hidden' }} absolute right-2 top-1/2 -translate-y-1/2
                               text-gray-400 hover:text-gray-600 text-sm leading-none">×</button>
                    <input type="hidden" name="metadata_id" id="metadataId" value="{{ $activeMetadataId }}">
                    {{--
                        Suggestion box — muncul saat focus (semua item) atau saat mengetik (filtered).
                        max-h ditinggikan supaya pengguna bisa scroll dan melihat banyak pilihan.
                    --}}
                    <div id="metadataSuggestions"
                         class="hidden absolute z-20 w-full mt-1 bg-white border border-gray-200
                                rounded-lg shadow-lg max-h-72 overflow-y-auto"></div>
                </div>
            </div>

            {{-- ── Kabupaten ── --}}
            <div>
                <label class="block text-xs text-gray-500 font-medium mb-1">
                    <i class="fas fa-map-marker-alt mr-1 text-gray-400"></i> Kabupaten
                </label>
                <select name="kabupaten" id="kabupatenSelect"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm
                           focus:outline-none focus:ring-2 focus:ring-sky-400 bg-white"
                    onchange="onKabupatenChange(this.value); onFilterChange()">
                    <option value="">Semua Kabupaten</option>
                    @foreach($kabupatenList as $kab)
                        <option value="{{ $kab }}" {{ $activeKabupaten == $kab ? 'selected' : '' }}>
                            {{ $kab }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- ── Tahun (Searchable + Show All on Focus) ── --}}
            <div>
                <label class="block text-xs text-gray-500 font-medium mb-1">
                    <i class="fas fa-calendar mr-1 text-gray-400"></i> Tahun
                </label>
                <div class="relative" id="yearDropdownWrap">
                    <input type="text" id="yearSearch" name="year"
                        placeholder="Klik atau ketik tahun..."
                        autocomplete="off"
                        value="{{ $activeYear }}"
                        maxlength="4"
                        oninput="onYearInput(); onFilterChange()"
                        onfocus="onYearFocus()"
                        class="w-full border border-gray-300 rounded-md pl-8 pr-3 py-2 text-sm
                               focus:outline-none focus:ring-2 focus:ring-sky-400 bg-white cursor-pointer">
                    <i class="fas fa-chevron-down absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none" id="yearChevron"></i>
                    <div id="yearSuggestions"
                         class="hidden absolute z-20 w-full mt-1 bg-white border border-gray-200
                                rounded-lg shadow-lg max-h-48 overflow-y-auto"></div>
                </div>
            </div>

            {{-- ── Kecamatan (dependent) ── --}}
            <div>
                <label class="block text-xs text-gray-500 font-medium mb-1">
                    <i class="fas fa-map mr-1 text-gray-400"></i> Kecamatan
                </label>
                <select name="kecamatan" id="kecamatanSelect"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm
                           focus:outline-none focus:ring-2 focus:ring-sky-400 bg-white
                           disabled:bg-gray-50 disabled:text-gray-400 disabled:cursor-not-allowed"
                    onchange="onKecamatanChange(this.value); onFilterChange()"
                    {{ !$activeKabupaten ? 'disabled' : '' }}>
                    <option value="">
                        {{ $activeKabupaten ? 'Semua Kecamatan' : '— Pilih kabupaten dulu —' }}
                    </option>
                </select>
            </div>

            {{-- ── Desa (dependent) ── --}}
            <div>
                <label class="block text-xs text-gray-500 font-medium mb-1">
                    <i class="fas fa-home mr-1 text-gray-400"></i> Desa
                </label>
                <select name="desa" id="desaSelect"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm
                           focus:outline-none focus:ring-2 focus:ring-sky-400 bg-white
                           disabled:bg-gray-50 disabled:text-gray-400 disabled:cursor-not-allowed"
                    onchange="onFilterChange()"
                    {{ !$activeKecamatan ? 'disabled' : '' }}>
                    <option value="">
                        {{ $activeKecamatan ? 'Semua Desa' : '— Pilih kecamatan dulu —' }}
                    </option>
                </select>
            </div>

            {{-- ── Search teks ── --}}
            <div>
                <label class="block text-xs text-gray-500 font-medium mb-1">
                    <i class="fas fa-search mr-1 text-gray-400"></i> Cari
                </label>
                <input type="text" name="search" value="{{ $activeSearch }}"
                    placeholder="Nama metadata, nilai..."
                    oninput="onFilterChange()"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm
                           focus:outline-none focus:ring-2 focus:ring-sky-400">
            </div>
        </div>

        {{-- Tombol aksi filter — hanya muncul jika ada filter dipilih --}}
        <div class="flex gap-2 items-center flex-wrap pt-1">

            {{-- Tombol Terapkan: tersembunyi, muncul via JS saat filter berubah --}}
            <button type="submit" id="btnApplyFilter"
                class="{{ $hasFilter ? '' : 'hidden' }} bg-sky-500 hover:bg-sky-600 text-white px-5 py-2
                       rounded-md text-sm font-semibold transition-colors flex items-center gap-2">
                <i class="fas fa-filter"></i> Terapkan Filter
            </button>

            {{-- Reset: muncul hanya jika ada filter aktif --}}
            @if($hasFilter)
                <a href="{{ route('data.index') }}"
                   class="border border-gray-300 hover:bg-gray-50 text-gray-500 px-4 py-2
                          rounded-md text-sm transition-colors flex items-center gap-1.5">
                    <i class="fas fa-times text-xs"></i> Reset
                </a>
            @endif

            {{-- Badge filter aktif --}}
            @if($hasFilter)
                <div class="flex flex-wrap gap-1.5 ml-1">
                    @if($activeMetadataNama)
                        <span class="bg-sky-50 text-sky-600 border border-sky-200 text-xs px-2 py-0.5 rounded-full">
                            <i class="fas fa-database mr-1 text-sky-300 text-xs"></i>{{ $activeMetadataNama }}
                        </span>
                    @endif
                    @if($activeKabupaten)
                        <span class="bg-emerald-50 text-emerald-600 border border-emerald-200 text-xs px-2 py-0.5 rounded-full">
                            <i class="fas fa-map-marker-alt mr-1 text-emerald-300 text-xs"></i>{{ $activeKabupaten }}
                        </span>
                    @endif
                    @if($activeKecamatan)
                        <span class="bg-emerald-50 text-emerald-600 border border-emerald-200 text-xs px-2 py-0.5 rounded-full">
                            {{ $activeKecamatan }}
                        </span>
                    @endif
                    @if($activeDesa)
                        <span class="bg-emerald-50 text-emerald-600 border border-emerald-200 text-xs px-2 py-0.5 rounded-full">
                            {{ $activeDesa }}
                        </span>
                    @endif
                    @if($activeYear)
                        <span class="bg-amber-50 text-amber-600 border border-amber-200 text-xs px-2 py-0.5 rounded-full">
                            <i class="fas fa-calendar mr-1 text-amber-300 text-xs"></i>{{ $activeYear }}
                        </span>
                    @endif
                </div>
            @endif
        </div>
    </form>
    <hr class="my-3">

    {{-- ═══════════════════════════════════════════════════════ --}}
    {{-- TABEL — hanya tampil jika ada filter aktif             --}}
    {{-- ═══════════════════════════════════════════════════════ --}}
    @if(!$hasFilter)
        {{-- State awal: belum ada filter --}}
        <div class="mt-8 flex flex-col items-center gap-3 py-16 text-gray-400">
            <div class="w-16 h-16 rounded-full flex items-center justify-center"
                 style="background:#f0f9ff;">
                <i class="fas fa-filter text-2xl" style="color:#bae6fd;"></i>
            </div>
            <p class="font-semibold text-gray-500 text-base">Pilih filter untuk menampilkan data</p>
            <p class="text-sm text-gray-400 text-center max-w-sm">
                Gunakan filter di atas (metadata, lokasi, tahun) lalu klik
                <strong class="text-sky-500">Terapkan Filter</strong>
            </p>
        </div>

    @elseif($data && $data->count() > 0)

        {{-- SELECTION BAR --}}
        <div id="selectionBar"
             class="hidden mt-4 flex items-center justify-between px-4 py-2.5 rounded-lg text-sm"
             style="background:#f5f3ff; border:1px solid #ddd6fe;">
            <p style="color:#7c3aed;" class="font-medium flex items-center gap-2">
                <i class="fas fa-check-square"></i>
                <span id="selectionText">0 data dipilih</span>
            </p>
            <button onclick="clearSelection()" style="color:#7c3aed;"
                    class="text-xs font-medium hover:underline">
                <i class="fas fa-times mr-1"></i> Batalkan Pilihan
            </button>
        </div>

        {{-- TABEL DATA --}}
        <div class="mt-3 border rounded-lg overflow-hidden">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider border-b">
                    <tr>
                        <th class="px-4 py-3 w-10">
                            <input type="checkbox" id="checkAll" onchange="toggleAll(this)"
                                   class="rounded border-gray-300 cursor-pointer">
                        </th>
                        <th class="px-4 py-3 font-semibold">No</th>
                        <th class="px-4 py-3 font-semibold">Metadata</th>
                        <th class="px-4 py-3 font-semibold">Lokasi</th>
                        <th class="px-4 py-3 font-semibold">Waktu</th>
                        <th class="px-4 py-3 font-semibold">Nilai</th>
                        <th class="px-4 py-3 font-semibold">Input Oleh</th>
                        <th class="px-4 py-3 font-semibold text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($data as $index => $row)
                        @php
                            $time = $row->time;

                            $bulanList = [
                                1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
                                7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'
                            ];

                            $year    = $time->year ?? null;
                            $quarter = $time->quarter ?? null;
                            $month   = $time->month ?? null;

                            $bulanText = ($month && $month != 0) ? $bulanList[$month] : 'All';
                            $quarterText = ($quarter && $quarter != 0) ? 'Q'.$quarter : 'All';

                            $lokasiText = '-';

                            if($row->location){
                                $lokasiText = implode(', ', array_filter([
                                    $row->location->kabupaten ?? null,
                                    $row->location->kecamatan ?? null,
                                    $row->location->desa ?? null
                                ]));
                            }
                        @endphp

                        <tr class="hover:bg-purple-50 transition-colors data-row"
                            id="row-{{ $row->id }}"
                            data-id="{{ $row->id }}"
                            data-metadata="{{ e($row->metadata->nama ?? '-') }}"
                            data-metadata-id="{{ $row->metadata_id }}"
                            data-lokasi="{{ e($lokasiText) }}"
                            data-waktu="{{ $year ?? 'All' }}"
                            data-nilai="{{ $row->number_value ?? 0 }}">

                            <td class="px-4 py-3">
                                <input type="checkbox"
                                    class="row-check rounded border-gray-300 cursor-pointer"
                                    value="{{ $row->id }}"
                                    onchange="onRowCheck(this)">
                            </td>

                            <td class="px-4 py-3 text-gray-400 text-xs">
                                {{ $data->firstItem() + $index }}
                            </td>

                            {{-- METADATA --}}
                            <td class="px-4 py-3">
                                <p class="font-semibold text-gray-800">
                                    {{ $row->metadata->nama ?? '-' }}
                                </p>

                                @if($row->metadata?->satuan_data)
                                    <p class="text-xs text-gray-400">
                                        {{ $row->metadata->satuan_data }}
                                    </p>
                                @endif
                            </td>

                            {{-- LOKASI --}}
                            <td class="px-4 py-3 text-xs">

                                @if($row->location)

                                    <p class="font-medium text-gray-700">
                                        {{ $row->location->kabupaten ?? 'All' }}
                                    </p>

                                    <p class="text-gray-400">
                                        {{ $row->location->kecamatan ?? 'All' }},
                                        {{ $row->location->desa ?? 'All' }}
                                    </p>

                                @else
                                    <span class="text-gray-400">-</span>
                                @endif

                            </td>

                            {{-- WAKTU --}}
                            <td class="px-4 py-3 text-xs">

                                @if($row->time)

                                    <p class="font-medium text-gray-700">
                                        {{ $year ?? 'All' }}
                                    </p>

                                    <p class="text-gray-400">
                                        {{ $quarterText }} · {{ $bulanText }}
                                    </p>

                                @else
                                    <span class="text-gray-400">-</span>
                                @endif

                            </td>

                            {{-- NILAI --}}
                            <td class="px-4 py-3">

                                @if(!is_null($row->number_value))

                                    <span class="font-semibold text-gray-800">
                                        {{ rtrim(rtrim(number_format($row->number_value, 2, ',', '.'), '0'), ',') }}
                                    </span>

                                    <span class="text-xs font-normal text-gray-400">
                                        {{ $row->metadata?->satuan_data }}
                                    </span>

                                @else
                                    <span class="text-gray-400 text-xs">-</span>
                                @endif

                            </td>

                            {{-- USER --}}
                            <td class="px-4 py-3 text-xs text-gray-500">
                                {{ $row->user->name ?? '-' }}
                            </td>

                            {{-- AKSI --}}
                            <td class="px-4 py-3 text-center">

                                <a href="{{ route('data.show', $row->id) }}"
                                class="text-sky-500 hover:text-sky-700 text-xs font-medium transition-colors">

                                    <i class="fas fa-eye"></i> Detail

                                </a>

                            </td>

                        </tr>

                        @endforeach
                </tbody>
            </table>
        </div>

        {{-- PAGINATION --}}
        @if($data->hasPages())
            <div class="mt-5 flex flex-col sm:flex-row items-center justify-between gap-3 text-sm text-gray-500">
                <p>Menampilkan {{ $data->firstItem() }}–{{ $data->lastItem() }} dari {{ number_format($data->total()) }} data</p>
                {{ $data->links() }}
            </div>
        @endif

    @else
        {{-- Filter diterapkan tapi data kosong --}}
        <div class="mt-8 flex flex-col items-center gap-3 py-16 text-gray-400">
            <i class="fas fa-search text-4xl text-gray-300"></i>
            <p class="font-medium text-gray-500">Tidak ada data yang sesuai filter</p>
            <a href="{{ route('data.index') }}"
               class="text-sky-500 hover:text-sky-700 text-sm font-medium">
                <i class="fas fa-times mr-1"></i> Reset Filter
            </a>
        </div>
    @endif

</div>

{{-- ═══════════════════════════════════════════════════════════ --}}
{{-- MODAL SIMPAN TEMPLATE                                      --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
<div id="modalTemplate"
     class="fixed inset-0 z-50 hidden flex items-center justify-center p-4"
     style="background:rgba(0,0,0,0.45);">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg max-h-[90vh] flex flex-col overflow-hidden">

        {{-- Header --}}
        <div class="px-6 py-4 shrink-0"
             style="background:linear-gradient(135deg,#8b5cf6,#6d28d9);">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-white font-bold text-base flex items-center gap-2">
                        <i class="fas fa-bookmark"></i> Simpan Template
                    </h3>
                    <p class="text-purple-200 text-xs mt-0.5">
                        Menyimpan filter aktif beserta data yang dicentang (opsional)
                    </p>
                </div>
                <button onclick="closeTemplateModal()"
                        class="text-purple-200 hover:text-white text-2xl leading-none">×</button>
            </div>
        </div>

        {{-- Body --}}
        <div class="p-6 overflow-y-auto flex-1 space-y-5">

            {{-- Input nama --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                    Nama Template <span class="text-red-500">*</span>
                </label>
                <input type="text" id="templateNama"
                    placeholder="cth: Data Penduduk Badung 2023"
                    class="w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm
                           focus:outline-none focus:ring-2 focus:ring-purple-400">
                <p id="templateNamaError"
                   class="hidden mt-1 text-xs text-red-500">
                    <i class="fas fa-exclamation-circle mr-1"></i> Nama template wajib diisi.
                </p>
            </div>

            {{-- Filter yang akan ikut disimpan --}}
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">
                    <i class="fas fa-filter mr-1"></i> Filter Tersimpan
                </p>
                <div id="modalFilterBadges" class="flex flex-wrap gap-1.5 min-h-6"></div>
            </div>

            {{-- Tabel data terpilih (opsional) --}}
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2
                           flex items-center justify-between">
                    Data Terpilih
                    <span id="modalDataCount"
                          class="font-bold px-2 py-0.5 rounded-full text-xs"
                          style="background:#f5f3ff; color:#7c3aed;">0 baris</span>
                </p>
                <div id="modalDataEmpty"
                     class="text-xs text-gray-400 italic py-3 text-center border rounded-lg bg-gray-50">
                    Tidak ada data dicentang — template hanya menyimpan filter
                </div>
                <div id="modalDataTableWrap" class="hidden border rounded-lg overflow-hidden max-h-52 overflow-y-auto">
                    <table class="w-full text-xs">
                        <thead class="bg-gray-50 border-b text-gray-500 sticky top-0">
                            <tr>
                                <th class="px-3 py-2 text-left font-semibold">Metadata</th>
                                <th class="px-3 py-2 text-left font-semibold">Lokasi</th>
                                <th class="px-3 py-2 text-left font-semibold">Tahun</th>
                                <th class="px-3 py-2 text-left font-semibold">Nilai</th>
                                <th class="px-3 py-2 w-8"></th>
                            </tr>
                        </thead>
                        <tbody id="modalDataBody" class="divide-y divide-gray-100"></tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="px-6 py-4 border-t bg-gray-50 flex justify-end gap-2 shrink-0">
            <button onclick="closeTemplateModal()"
                class="border border-gray-300 text-gray-500 hover:bg-gray-100
                       px-4 py-2 rounded-md text-sm transition-colors">
                Batal
            </button>
            <button onclick="submitTemplate()"
                class="px-5 py-2 rounded-md text-sm font-semibold text-white
                       flex items-center gap-2 transition-colors"
                style="background:#8b5cf6;"
                onmouseover="this.style.background='#7c3aed'"
                onmouseout="this.style.background='#8b5cf6'">
                <i class="fas fa-save"></i> Simpan Template
            </button>
        </div>
    </div>
</div>

{{-- Form tersembunyi untuk submit --}}
<form id="formSaveTemplate" action="{{ route('data.template.store') }}" method="POST" class="hidden">
    @csrf
    <input type="hidden" name="nama_tampilan"      id="formTemplateName">
    <input type="hidden" name="filter_metadata_id" id="formFilterMetadataId" value="{{ $activeMetadataId }}">
    <input type="hidden" name="filter_kabupaten"   id="formFilterKabupaten"  value="{{ $activeKabupaten }}">
    <input type="hidden" name="filter_kecamatan"   id="formFilterKecamatan"  value="{{ $activeKecamatan }}">
    <input type="hidden" name="filter_desa"        id="formFilterDesa"       value="{{ $activeDesa }}">
    <input type="hidden" name="filter_year"        id="formFilterYear"       value="{{ $activeYear }}">
    <div id="formDataIds"></div>
</form>

<script>
// ────────────────────────────────────────────────────────────────
// LIVE CLOCK
// ────────────────────────────────────────────────────────────────
function updateDateTime() {
    const now = new Date();
    document.getElementById('current-date').textContent =
        now.toLocaleDateString('id-ID', { weekday:'long', year:'numeric', month:'long', day:'numeric' });
    document.getElementById('current-time').textContent =
        now.toLocaleTimeString('id-ID', { hour:'2-digit', minute:'2-digit', second:'2-digit' }) + ' WITA';
}
updateDateTime();
setInterval(updateDateTime, 1000);

// ════════════════════════════════════════════════════════════════
// HELPER — baca state filter yang sedang aktif di form
// ════════════════════════════════════════════════════════════════
function getActiveFilter() {
    return {
        metadataId:   document.getElementById('metadataId').value.trim(),
        metadataNama: document.getElementById('metadataSearch').value.trim(),
        kabupaten:    document.getElementById('kabupatenSelect').value.trim(),
        kecamatan:    document.getElementById('kecamatanSelect').value.trim(),
        desa:         document.getElementById('desaSelect').value.trim(),
        year:         document.getElementById('yearSearch').value.trim(),
    };
}

function hideSuggestions(id) {
    document.getElementById(id)?.classList.add('hidden');
}
// ════════════════════════════════════════════════════════════════
// TOMBOL "TERAPKAN FILTER" & "SIMPAN TEMPLATE"
// Keduanya muncul/sembunyi berdasarkan filter yang dipilih.
// Tombol Simpan Template muncul jika minimal 1 filter aktif
// (metadata ATAU tahun ATAU lokasi) — tidak harus semuanya.
// ════════════════════════════════════════════════════════════════
function onFilterChange() {
    const f = getActiveFilter();

    // Ada filter jika salah satu diisi
    const hasAny = f.metadataId || f.kabupaten || f.year;

    // Tombol Terapkan Filter
    const btnApply = document.getElementById('btnApplyFilter');
    if (hasAny) btnApply.classList.remove('hidden');
    else        btnApply.classList.add('hidden');

    // Tombol Simpan Template — muncul jika ada filter apapun
    const btnSave = document.getElementById('btnSaveTemplate');
    if (hasAny) btnSave.classList.remove('hidden');
    else        btnSave.classList.add('hidden');

    // Perbarui selection bar
    updateSelectionUI();
}

// ════════════════════════════════════════════════════════════════
// METADATA DROPDOWN — show all on focus, filter on input
// ════════════════════════════════════════════════════════════════
const metadataSearchUrl = '{{ route("data.search_metadata") }}';
let metadataTimeout     = null;

/**
 * Render daftar metadata ke suggestion box.
 * Jika results kosong tampilkan pesan, jika ada highlight item yang
 * sudah terpilih saat ini.
 */
function renderMetadataSuggestions(results) {
    const box       = document.getElementById('metadataSuggestions');
    const currentId = document.getElementById('metadataId').value;

    if (results.length === 0) {
        box.innerHTML = '<p class="px-4 py-3 text-xs text-gray-400 text-center">Tidak ada hasil</p>';
        box.classList.remove('hidden');
        return;
    }

    box.innerHTML = results.map(m => {
        const isSelected = String(m.metadata_id) === String(currentId);
        return `<button type="button"
            onclick="selectMetadata(${m.metadata_id}, '${m.nama.replace(/'/g,"\\'")}')"
            class="w-full text-left px-4 py-2.5 flex items-start gap-2.5 border-b border-gray-50
                   last:border-0 transition-colors
                   ${isSelected
                       ? 'bg-sky-50 hover:bg-sky-100'
                       : 'hover:bg-gray-50'
                   }">
            <span class="mt-0.5 shrink-0 w-4 h-4 flex items-center justify-center">
                ${isSelected
                    ? '<i class="fas fa-check text-sky-500 text-xs"></i>'
                    : '<i class="fas fa-database text-gray-300 text-xs"></i>'
                }
            </span>
            <span class="flex flex-col gap-0.5 min-w-0">
                <span class="font-medium text-gray-800 text-xs leading-snug">${m.nama}</span>
                <span class="text-gray-400 text-xs truncate">
                    ${m.klasifikasi || ''}${m.satuan_data ? ' · ' + m.satuan_data : ''}
                </span>
            </span>
        </button>`;
    }).join('');

    box.classList.remove('hidden');
}

/**
 * onMetadataFocus — dipanggil saat input difokus.
 * Langsung tampilkan semua metadata (q kosong = semua).
 * Jika sudah ada cache dari request sebelumnya, gunakan cache.
 */
let metadataAllCache = null; // cache hasil fetch semua metadata

function onMetadataFocus() {
    // Jika suggestion sudah terbuka, tidak perlu fetch ulang
    const box = document.getElementById('metadataSuggestions');
    if (!box.classList.contains('hidden')) return;

    // Gunakan cache jika ada
    if (metadataAllCache) {
        renderMetadataSuggestions(metadataAllCache);
        return;
    }

    // Tampilkan loading sementara fetch berjalan
    box.innerHTML = '<p class="px-4 py-3 text-xs text-gray-400 text-center"><i class="fas fa-circle-notch fa-spin mr-1"></i>Memuat...</p>';
    box.classList.remove('hidden');

    fetch(`${metadataSearchUrl}?q=`)
        .then(r => r.json())
        .then(results => {
            metadataAllCache = results;
            renderMetadataSuggestions(results);
        })
        .catch(() => {
            box.innerHTML = '<p class="px-4 py-3 text-xs text-red-400 text-center">Gagal memuat data</p>';
        });
}

/**
 * onMetadataInput — dipanggil saat pengguna mengetik.
 * Reset cache agar hasil tidak stale, lalu fetch dengan query.
 */
function onMetadataInput() {
    clearTimeout(metadataTimeout);
    const q = document.getElementById('metadataSearch').value.trim();

    // Jika input dikosongkan → reset id & tampilkan semua
    if (q.length === 0) {
        document.getElementById('metadataId').value = '';
        document.getElementById('clearMetadata').classList.add('hidden');

        // Tampilkan semua (pakai cache jika ada)
        if (metadataAllCache) {
            renderMetadataSuggestions(metadataAllCache);
        } else {
            onMetadataFocus(); // fetch semua
        }

        onFilterChange();
        return;
    }

    // Ketik sesuatu → fetch filtered (jangan pakai cache semua)
    const box = document.getElementById('metadataSuggestions');
    box.innerHTML = '<p class="px-4 py-3 text-xs text-gray-400 text-center"><i class="fas fa-circle-notch fa-spin mr-1"></i>Mencari...</p>';
    box.classList.remove('hidden');

    metadataTimeout = setTimeout(() => {
        fetch(`${metadataSearchUrl}?q=${encodeURIComponent(q)}`)
            .then(r => r.json())
            .then(results => renderMetadataSuggestions(results))
            .catch(() => {
                box.innerHTML = '<p class="px-4 py-3 text-xs text-red-400 text-center">Gagal memuat data</p>';
            });
    }, 250);
}

function selectMetadata(id, nama) {
    document.getElementById('metadataSearch').value = nama;
    document.getElementById('metadataId').value     = id;
    document.getElementById('clearMetadata').classList.remove('hidden');
    hideSuggestions('metadataSuggestions');
    onFilterChange();
}

function clearMetadataFilter() {
    document.getElementById('metadataSearch').value = '';
    document.getElementById('metadataId').value     = '';
    document.getElementById('clearMetadata').classList.add('hidden');
    hideSuggestions('metadataSuggestions');
    onFilterChange();
}

// ════════════════════════════════════════════════════════════════
// YEAR DROPDOWN — show all on focus, filter on input
// ════════════════════════════════════════════════════════════════
const yearSearchUrl = '{{ route("data.search_year") }}';
let yearTimeout     = null;

/**
 * Render daftar tahun ke suggestion box.
 * Item yang sudah terpilih diberi highlight.
 */
function renderYearSuggestions(years) {

    const box         = document.getElementById('yearSuggestions');
    const currentYear = document.getElementById('yearSearch').value.trim();

    let html = `
        <button type="button"
            onclick="selectYear('')"
            class="w-full text-left px-4 py-2.5 flex items-center gap-2 border-b border-gray-100 hover:bg-gray-50">
            <i class="fas fa-layer-group text-gray-300 text-xs"></i>
            <span class="text-sm font-medium text-gray-700">Semua Tahun</span>
        </button>
    `;

    if (years.length === 0) {
        html += '<p class="px-4 py-3 text-xs text-gray-400 text-center">Tidak ada tahun tersedia</p>';
        box.innerHTML = html;
        box.classList.remove('hidden');
        return;
    }

    html += years.map(y => {

        const isSelected = String(y) === currentYear;

        return `<button type="button"
            onclick="selectYear(${y})"
            class="w-full text-left px-4 py-2.5 flex items-center gap-2 border-b border-gray-50
                   last:border-0 transition-colors
                   ${isSelected ? 'bg-amber-50 hover:bg-amber-100 text-amber-700 font-semibold' : 'hover:bg-gray-50 text-gray-700'}">

            <i class="fas fa-calendar-alt text-xs ${isSelected ? 'text-amber-400' : 'text-gray-300'}"></i>
            <span class="text-sm">${y}</span>

        </button>`;
    }).join('');

    box.innerHTML = html;
    box.classList.remove('hidden');
}

let yearAllCache = null; // cache semua tahun

/**
 * onYearFocus — tampilkan semua tahun saat input difokus.
 */
function onYearFocus() {
    const box = document.getElementById('yearSuggestions');
    if (!box.classList.contains('hidden')) return;

    if (yearAllCache) {
        renderYearSuggestions(yearAllCache);
        return;
    }

    box.innerHTML = '<p class="px-4 py-3 text-xs text-gray-400 text-center"><i class="fas fa-circle-notch fa-spin mr-1"></i>Memuat...</p>';
    box.classList.remove('hidden');

    fetch(`${yearSearchUrl}?q=`)
        .then(r => r.json())
        .then(years => {
            yearAllCache = years;
            renderYearSuggestions(years);
        })
        .catch(() => {
            box.innerHTML = '<p class="px-4 py-3 text-xs text-red-400 text-center">Gagal memuat data</p>';
        });
}

/**
 * onYearInput — filter daftar tahun berdasarkan input.
 */
function onYearInput() {
    clearTimeout(yearTimeout);
    const q = document.getElementById('yearSearch').value.trim();

    // Input kosong → tampilkan semua
    if (q.length === 0) {
        if (yearAllCache) {
            renderYearSuggestions(yearAllCache);
        } else {
            onYearFocus();
        }
        return;
    }

    // Filter dari cache jika sudah ada (tidak perlu fetch ulang)
    if (yearAllCache) {
        const filtered = yearAllCache.filter(y => String(y).startsWith(q));
        renderYearSuggestions(filtered);
        return;
    }

    // Fallback: fetch ke server
    const box = document.getElementById('yearSuggestions');
    yearTimeout = setTimeout(() => {
        fetch(`${yearSearchUrl}?q=${encodeURIComponent(q)}`)
            .then(r => r.json())
            .then(years => renderYearSuggestions(years))
            .catch(() => {
                box.innerHTML = '<p class="px-4 py-3 text-xs text-red-400 text-center">Gagal memuat data</p>';
            });
    }, 200);
}

function selectYear(year) {

    document.getElementById('yearSearch').value = year ? year : 'Semua Tahun';

    hideSuggestions('yearSuggestions');

    onFilterChange();
}

// ════════════════════════════════════════════════════════════════
// TUTUP DROPDOWN — klik di luar atau tekan Escape
// ════════════════════════════════════════════════════════════════
document.addEventListener('click', function (e) {
    if (!document.getElementById('metadataDropdownWrap').contains(e.target)) {
        hideSuggestions('metadataSuggestions');
        // Jika teks tidak cocok dengan ID yang terpilih, reset
        const idVal   = document.getElementById('metadataId').value;
        const txtVal  = document.getElementById('metadataSearch').value.trim();
        if (!idVal && txtVal) {
            // Pengguna mengetik tapi tidak memilih → biarkan (search teks bebas tidak didukung)
            // Bersihkan agar tidak ada state gantung
            document.getElementById('metadataSearch').value = '';
            document.getElementById('clearMetadata').classList.add('hidden');
            onFilterChange();
        }
    }
    if (!document.getElementById('yearDropdownWrap').contains(e.target)) {
        hideSuggestions('yearSuggestions');
    }
});

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        hideSuggestions('metadataSuggestions');
        hideSuggestions('yearSuggestions');
        closeTemplateModal();
    }
});


// ────────────────────────────────────────────────────────────────
// DEPENDENT DROPDOWN LOKASI
// ────────────────────────────────────────────────────────────────
const kecamatanUrl = '{{ route("data.kecamatan") }}';
const desaUrl      = '{{ route("data.desa") }}';

function onKabupatenChange(kabupaten) {
    const kecSel  = document.getElementById('kecamatanSelect');
    const desaSel = document.getElementById('desaSelect');

    kecSel.innerHTML  = '<option value="">Semua Kecamatan</option>';
    desaSel.innerHTML = '<option value="">Semua Desa</option>';
    desaSel.disabled  = true;

    if (!kabupaten) {
        kecSel.disabled  = true;
        kecSel.innerHTML = '<option value="">— Pilih kabupaten dulu —</option>';
        return;
    }

    kecSel.disabled  = false;
    kecSel.innerHTML = '<option value="">Memuat...</option>';

    fetch(`${kecamatanUrl}?kabupaten=${encodeURIComponent(kabupaten)}`)
        .then(r => r.json())
        .then(list => {
            kecSel.innerHTML = '<option value="">Semua Kecamatan</option>';
            list.forEach(k => {
                const opt       = document.createElement('option');
                opt.value       = k;
                opt.textContent = k;
                if (k === '{{ request("kecamatan") }}') opt.selected = true;
                kecSel.appendChild(opt);
            });
            if ('{{ request("kecamatan") }}') onKecamatanChange('{{ request("kecamatan") }}');
        });
}

function onKecamatanChange(kecamatan) {
    const desaSel = document.getElementById('desaSelect');
    desaSel.innerHTML = '<option value="">Semua Desa</option>';

    if (!kecamatan) {
        desaSel.disabled = true;
        return;
    }

    desaSel.disabled  = false;
    desaSel.innerHTML = '<option value="">Memuat...</option>';

    fetch(`${desaUrl}?kecamatan=${encodeURIComponent(kecamatan)}`)
        .then(r => r.json())
        .then(list => {
            desaSel.innerHTML = '<option value="">Semua Desa</option>';
            list.forEach(d => {
                const opt       = document.createElement('option');
                opt.value       = d;
                opt.textContent = d;
                if (d === '{{ request("desa") }}') opt.selected = true;
                desaSel.appendChild(opt);
            });
        });
}

// Restore state saat halaman load (ada filter aktif dari server)
document.addEventListener('DOMContentLoaded', function () {
    const kabupaten = '{{ request("kabupaten") }}';
    if (kabupaten) onKabupatenChange(kabupaten);

    // Inisialisasi tampilan tombol sesuai filter aktif dari server
    onFilterChange();
});

// ────────────────────────────────────────────────────────────────
// CHECKLIST & SELECTION
// ────────────────────────────────────────────────────────────────
let selectedRows = {};

function onRowCheck(checkbox) {
    const row = checkbox.closest('tr');
    const id  = row.dataset.id;
    if (checkbox.checked) {
        selectedRows[id] = {
            id:         id,
            metadata:   row.dataset.metadata,
            metadataId: row.dataset.metadataId,
            lokasi:     row.dataset.lokasi,
            waktu:      row.dataset.waktu,
            nilai:      row.dataset.nilai,
        };
        row.style.background = '#f5f3ff';
    } else {
        delete selectedRows[id];
        row.style.background = '';
    }
    updateSelectionUI();
}

function toggleAll(masterCb) {
    document.querySelectorAll('.row-check').forEach(cb => {
        cb.checked = masterCb.checked;
        onRowCheck(cb);
    });
}

function clearSelection() {
    selectedRows = {};
    document.querySelectorAll('.row-check').forEach(cb => cb.checked = false);
    document.getElementById('checkAll').checked = false;
    document.querySelectorAll('.data-row').forEach(r => r.style.background = '');
    updateSelectionUI();
}

function updateSelectionUI() {
    const count = Object.keys(selectedRows).length;

    // Selection bar (hanya tampil jika ada baris dicentang)
    const bar = document.getElementById('selectionBar');
    if (bar) bar.classList.toggle('hidden', count === 0);

    const selText = document.getElementById('selectionText');
    if (selText) selText.textContent = count + ' data dipilih';
}

function formatStatNumber(value) {

    if (value === null || value === undefined || value === '') {
        return '-';
    }

    let num = parseFloat(value);

    if (isNaN(num)) {
        return value;
    }

    let formatted = num.toLocaleString('id-ID', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2
    });

    return formatted;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.innerText = text;
    return div.innerHTML;
}

// ────────────────────────────────────────────────────────────────
// MODAL TEMPLATE
// ────────────────────────────────────────────────────────────────
function openTemplateModal() {
    // Reset form input
    document.getElementById('templateNama').value = '';
    document.getElementById('templateNamaError').classList.add('hidden');

    // Baca filter yang aktif saat ini dari form element
    const f = getActiveFilter();

    // Render badge filter
    const filterDefs = [
        { label: 'Metadata',  value: f.metadataNama, color: '#eff6ff', text: '#1d4ed8' },
        { label: 'Kabupaten', value: f.kabupaten,    color: '#f0fdf4', text: '#15803d' },
        { label: 'Kecamatan', value: f.kecamatan,    color: '#f0fdf4', text: '#15803d' },
        { label: 'Desa',      value: f.desa,         color: '#f0fdf4', text: '#15803d' },
        { label: 'Tahun',     value: f.year,         color: '#fffbeb', text: '#b45309' },
    ].filter(fd => fd.value);

    const filterBox = document.getElementById('modalFilterBadges');
    if (filterDefs.length === 0) {
        filterBox.innerHTML = '<span class="text-xs text-gray-400 italic">Tidak ada filter aktif</span>';
    } else {
        filterBox.innerHTML = filterDefs.map(fd =>
            `<span class="px-2.5 py-1 rounded-full text-xs font-medium border"
                   style="background:${fd.color}; color:${fd.text}; border-color:${fd.text}33;">
                <span style="opacity:0.6; margin-right:4px;">${fd.label}:</span>${fd.value}
            </span>`
        ).join('');
    }

    // Render tabel data terpilih (opsional)
    const rows  = Object.values(selectedRows);
    const count = rows.length;
    document.getElementById('modalDataCount').textContent = count + ' baris';

    if (count === 0) {
        document.getElementById('modalDataEmpty').classList.remove('hidden');
        document.getElementById('modalDataTableWrap').classList.add('hidden');
    } else {
        document.getElementById('modalDataEmpty').classList.add('hidden');
        document.getElementById('modalDataTableWrap').classList.remove('hidden');
        document.getElementById('modalDataBody').innerHTML = rows.map(r =>
            `<tr id="modal-row-${r.id}">

                <td class="px-3 py-2 font-medium text-gray-700">
                    ${escapeHtml(r.metadata || '-')}
                </td>

                <td class="px-3 py-2 text-gray-500 text-xs">
                    ${escapeHtml(r.lokasi && r.lokasi.trim() !== '' ? r.lokasi : 'All')}
                </td>

                <td class="px-3 py-2 text-gray-500 text-xs">
                    ${escapeHtml(r.waktu && r.waktu.trim() !== '' ? r.waktu : 'All')}
                </td>

                <td class="px-3 py-2 text-gray-700 font-semibold">
                    ${formatStatNumber(r.nilai)}
                </td>

                <td class="px-3 py-2 text-center">
                    <button onclick="removeFromModal('${r.id}')"
                            class="text-red-400 hover:text-red-600 transition-colors">
                        <i class="fas fa-times-circle"></i>
                    </button>
                </td>

            </tr>`
        ).join('');
    }

    // Sync nilai ke hidden form
    document.getElementById('formFilterMetadataId').value = f.metadataId;
    document.getElementById('formFilterKabupaten').value  = f.kabupaten;
    document.getElementById('formFilterKecamatan').value  = f.kecamatan;
    document.getElementById('formFilterDesa').value       = f.desa;
    document.getElementById('formFilterYear').value       = f.year;

    document.getElementById('modalTemplate').classList.remove('hidden');
    setTimeout(() => document.getElementById('templateNama').focus(), 100);
}

function removeFromModal(id) {
    document.getElementById('modal-row-' + id)?.remove();
    delete selectedRows[id];

    const tableRow = document.getElementById('row-' + id);
    if (tableRow) {
        tableRow.querySelector('.row-check').checked = false;
        tableRow.style.background = '';
    }

    const remaining = Object.keys(selectedRows).length;
    document.getElementById('modalDataCount').textContent = remaining + ' baris';
    if (remaining === 0) {
        document.getElementById('modalDataEmpty').classList.remove('hidden');
        document.getElementById('modalDataTableWrap').classList.add('hidden');
    }
    updateSelectionUI();
}

function closeTemplateModal() {
    document.getElementById('modalTemplate').classList.add('hidden');
}

function submitTemplate() {
    const nama = document.getElementById('templateNama').value.trim();
    if (!nama) {
        document.getElementById('templateNamaError').classList.remove('hidden');
        document.getElementById('templateNama').focus();
        return;
    }

    document.getElementById('formTemplateName').value = nama;

    // Masukkan data IDs yang dicentang (boleh kosong)
    document.getElementById('formDataIds').innerHTML =
        Object.keys(selectedRows)
            .map(id => `<input type="hidden" name="data_ids[]" value="${id}">`)
            .join('');

    document.getElementById('formSaveTemplate').submit();
}

// Tutup modal klik backdrop / Escape
document.getElementById('modalTemplate').addEventListener('click', function (e) {
    if (e.target === this) closeTemplateModal();
});
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeTemplateModal(); });

// ────────────────────────────────────────────────────────────────
// EXPORT JSON
// ────────────────────────────────────────────────────────────────
function exportJSON() {
    const rows    = document.querySelectorAll('.data-row');
    const payload = [];
    rows.forEach(row => {
        payload.push({
            id:          row.dataset.id,
            metadata:    row.dataset.metadata,
            metadata_id: row.dataset.metadataId,
            lokasi:      row.dataset.lokasi,
            tahun:       row.dataset.waktu,
            nilai:       row.dataset.nilai,
        });
    });
    const blob = new Blob([JSON.stringify(payload, null, 2)], { type: 'application/json' });
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href     = url;
    a.download = 'data_export_' + new Date().toISOString().slice(0,10) + '.json';
    a.click();
    URL.revokeObjectURL(url);
}
</script>
@endsection