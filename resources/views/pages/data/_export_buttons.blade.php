@php
    $exportParams = array_filter([
        'metadata_id' => request('metadata_id'),
        'kabupaten'   => request('kabupaten'),
        'year'        => request('year'),
    ]);

    $excelUrl = route('data.export.excel', $exportParams);
    $pdfUrl   = route('data.export.pdf',   $exportParams);
    $jsonUrl  = route('data.export.json',  $exportParams);
@endphp

{{-- ─── Export Dropdown ───────────────────────────── --}}
<div class="relative" 
     x-data="{ open: false }" 
     @keydown.escape="open = false" 
     @click.outside="open = false">

    {{-- Button --}}
    <button @click="open = !open"
            class="flex items-center gap-2 px-4 py-2 text-sm rounded-md font-semibold
                   text-white transition-colors shadow-sm bg-teal-700 hover:bg-teal-800">
        <i class="fas fa-file-export"></i>
        Export
        <i class="fas fa-chevron-down text-xs opacity-70 transition-transform"
           :class="{ 'rotate-180': open }"></i>
    </button>

    {{-- Dropdown --}}
    <div x-show="open"
         x-transition
         class="absolute right-0 mt-2 w-52 bg-white rounded-xl shadow-xl border border-gray-100 z-50 overflow-hidden"
         style="display:none;">

        {{-- Header --}}
        <div class="px-4 py-2.5 border-b bg-gray-50">
            <p class="text-xs font-semibold text-gray-500 uppercase">Export Data</p>
            @if(request('kabupaten'))
                <p class="text-xs text-gray-400 truncate">{{ request('kabupaten') }}</p>
            @endif
        </div>

        {{-- Excel --}}
        <a href="{{ $excelUrl }}"
           class="flex items-center gap-3 px-4 py-3 text-sm hover:bg-green-50 group"
           onclick="this.innerHTML='Loading...'">
            <i class="fas fa-file-excel text-green-600 w-5 text-center"></i>
            <div>
                <p class="font-semibold text-gray-700">Excel (.xlsx)</p>
                <p class="text-xs text-gray-400">Spreadsheet</p>
            </div>
        </a>

        {{-- PDF --}}
        <a href="{{ $pdfUrl }}" target="_blank"
           class="flex items-center gap-3 px-4 py-3 text-sm hover:bg-red-50 border-t">
            <i class="fas fa-file-pdf text-red-500 w-5 text-center"></i>
            <div>
                <p class="font-semibold text-gray-700">PDF</p>
                <p class="text-xs text-gray-400">Preview & cetak</p>
            </div>
        </a>

        {{-- JSON --}}
        <a href="{{ $jsonUrl }}" target="_blank"
           class="flex items-center gap-3 px-4 py-3 text-sm hover:bg-blue-50 border-t">
            <i class="fas fa-code text-blue-500 w-5 text-center"></i>
            <div>
                <p class="font-semibold text-gray-700">JSON</p>
                <p class="text-xs text-gray-400">Raw data</p>
            </div>
        </a>

        {{-- Footer --}}
        <div class="px-4 py-2 border-t bg-gray-50">
            <p class="text-xs text-gray-400">
                Tahun:
                <span class="text-gray-600 font-medium">
                    {{ request('year') ?? 'Semua' }}
                </span>
            </p>
        </div>

    </div>
</div>