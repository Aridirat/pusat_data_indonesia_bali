@extends('layouts.main')

@section('content')
<div class="py-6">

    <a href="{{ route('dimensi_waktu.index') }}"
       class="flex items-center gap-1 font-semibold text-sky-600 ps-4 mb-4 hover:text-sky-900 text-sm transition-colors">
        <i class="fas fa-angle-left"></i> Kembali
    </a>

    <div class="mt-2 bg-white rounded-md shadow p-6 max-w-xl mx-auto">

        <h1 class="text-xl font-bold text-gray-800 mb-1">Tambah Dimensi Waktu</h1>
        <p class="text-sm text-gray-400 mb-6">Generate otomatis atau input manual hierarki waktu</p>

        {{-- ERROR ALERT --}}
        @if($errors->any())
            <div class="mb-5 flex items-start gap-3 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
                <i class="fas fa-exclamation-circle text-red-500 mt-0.5"></i>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <form action="{{ route('dimensi_waktu.store') }}" method="POST" id="formTambahWaktu">
            @csrf

            {{-- MODE SELECTOR --}}
            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Mode Input</label>
                <div class="flex gap-3">
                    <label class="flex-1 cursor-pointer">
                        <input type="radio" name="mode" value="full_year" id="modeFullYear"
                               class="sr-only peer"
                               {{ old('mode', 'full_year') === 'full_year' ? 'checked' : '' }}>
                        <div class="peer-checked:border-sky-500 peer-checked:bg-sky-50 peer-checked:text-sky-700
                                    border border-gray-200 rounded-lg p-3 text-sm text-center transition-all
                                    hover:border-sky-300">
                            <i class="fas fa-calendar-alt block text-lg mb-1"></i>
                            <span class="font-semibold">Generate Full Year</span>
                            <p class="text-xs text-gray-400 mt-0.5">365/366 baris sekaligus</p>
                        </div>
                    </label>
                    <label class="flex-1 cursor-pointer">
                        <input type="radio" name="mode" value="custom" id="modeCustom"
                               class="sr-only peer"
                               {{ old('mode') === 'custom' ? 'checked' : '' }}>
                        <div class="peer-checked:border-sky-500 peer-checked:bg-sky-50 peer-checked:text-sky-700
                                    border border-gray-200 rounded-lg p-3 text-sm text-center transition-all
                                    hover:border-sky-300">
                            <i class="fas fa-sliders-h block text-lg mb-1"></i>
                            <span class="font-semibold">Custom Hierarki</span>
                            <p class="text-xs text-gray-400 mt-0.5">1 baris, level bebas</p>
                        </div>
                    </label>
                </div>
            </div>

            {{-- ════════════════════════════════════════════ --}}
            {{-- SECTION: GENERATE FULL YEAR (mode lama)     --}}
            {{-- ════════════════════════════════════════════ --}}
            <div id="sectionFullYear">

                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Tahun <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="number"
                        name="tahun"
                        id="inputTahun"
                        min="1900"
                        max="2100"
                        value="{{ old('tahun') }}"
                        placeholder="Contoh: 2025"
                        class="w-full border @error('tahun') border-red-400 @else border-gray-300 @enderror
                               rounded-md px-4 py-2.5 text-gray-800 text-sm
                               focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-transparent
                               transition-shadow"
                        autocomplete="off"
                    >
                    @error('tahun')
                        <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                            <i class="fas fa-exclamation-circle"></i> {{ $message }}
                        </p>
                    @enderror
                    <p class="mt-1.5 text-xs text-gray-400">Rentang tahun yang diperbolehkan: 1900 – 2100</p>
                </div>

                {{-- PREVIEW CARD --}}
                <div id="previewCard"
                     class="hidden mb-6 border border-sky-200 bg-sky-50 rounded-lg p-4 text-sm text-sky-800">
                    <p class="font-semibold text-sky-700 mb-3 flex items-center gap-2">
                        <i class="fas fa-calendar-alt"></i>
                        Preview Generate
                    </p>
                    <div class="grid grid-cols-2 gap-y-2 gap-x-4">
                        <div>
                            <span class="text-sky-500 text-xs">Tahun</span>
                            <p id="prevTahun" class="font-bold text-sky-800 text-lg">-</p>
                        </div>
                        <div>
                            <span class="text-sky-500 text-xs">Total Hari</span>
                            <p id="prevTotalHari" class="font-bold text-sky-800 text-lg">-</p>
                        </div>
                        <div>
                            <span class="text-sky-500 text-xs">Dekade</span>
                            <p id="prevDekade" class="font-semibold">-</p>
                        </div>
                        <div>
                            <span class="text-sky-500 text-xs">Tahun Kabisat</span>
                            <p id="prevKabisat" class="font-semibold">-</p>
                        </div>
                    </div>
                    <div class="mt-3 pt-3 border-t border-sky-200">
                        <p class="text-xs text-sky-500 mb-2 font-medium">Distribusi per Kuartal</p>
                        <div class="grid grid-cols-4 gap-2" id="prevQuarters"></div>
                    </div>
                </div>

            </div>{{-- end sectionFullYear --}}

            {{-- ════════════════════════════════════════════ --}}
            {{-- SECTION: CUSTOM HIERARKI (mode baru)        --}}
            {{-- ════════════════════════════════════════════ --}}
            <div id="sectionCustom" class="hidden">

                <p class="text-xs text-gray-400 mb-4">
                    Isi dari level teratas sampai level yang diinginkan.
                    Level yang dikosongkan akan disimpan sebagai <strong>ALL (0)</strong>.
                </p>

                {{-- Hirarki: decade → year → quarter → month → day --}}
                @php
                    $hierarki = [
                        ['field' => 'custom_decade',  'label' => 'Dekade',  'icon' => 'fas fa-layer-group',   'placeholder' => 'Contoh: 2020', 'hint' => 'Kelipatan 10 (1900–2100)', 'min' => 1900, 'max' => 2100],
                        ['field' => 'custom_year',    'label' => 'Tahun',   'icon' => 'fas fa-calendar',      'placeholder' => 'Contoh: 2024', 'hint' => '1900–2100',               'min' => 1900, 'max' => 2100],
                        ['field' => 'custom_quarter', 'label' => 'Kuartal', 'icon' => 'fas fa-th-large',      'placeholder' => '1, 2, 3, atau 4', 'hint' => 'Q1=Jan-Mar, Q2=Apr-Jun, Q3=Jul-Sep, Q4=Okt-Des', 'min' => 1, 'max' => 4],
                        ['field' => 'custom_month',   'label' => 'Bulan',   'icon' => 'fas fa-calendar-week', 'placeholder' => '1–12',         'hint' => '1=Januari … 12=Desember', 'min' => 1,  'max' => 12],
                        ['field' => 'custom_day',     'label' => 'Hari',    'icon' => 'fas fa-calendar-day',  'placeholder' => '1–31',         'hint' => 'Tanggal dalam bulan',     'min' => 1,  'max' => 31],
                    ];
                @endphp

                <div class="space-y-4">
                    @foreach($hierarki as $i => $item)
                    <div class="relative">
                        {{-- Connector line (semua kecuali item pertama) --}}
                        @if($i > 0)
                        <div class="absolute -top-4 left-[18px] w-px h-4 bg-gray-200"></div>
                        @endif

                        <div class="flex items-start gap-3">
                            {{-- Icon dot --}}
                            <div class="mt-2.5 w-9 h-9 rounded-full bg-gray-100 flex items-center justify-center shrink-0">
                                <i class="{{ $item['icon'] }} text-gray-400 text-sm"></i>
                            </div>

                            {{-- Input --}}
                            <div class="flex-1">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    {{ $item['label'] }}
                                    <span class="text-gray-400 font-normal text-xs">(opsional)</span>
                                </label>
                                <input
                                    type="number"
                                    name="{{ $item['field'] }}"
                                    id="{{ $item['field'] }}"
                                    min="{{ $item['min'] }}"
                                    max="{{ $item['max'] }}"
                                    value="{{ old($item['field']) }}"
                                    placeholder="{{ $item['placeholder'] }}"
                                    class="w-full border @error($item['field']) border-red-400 @else border-gray-300 @enderror
                                           rounded-md px-3 py-2 text-gray-800 text-sm
                                           focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-transparent
                                           transition-shadow"
                                    autocomplete="off"
                                >
                                @error($item['field'])
                                    <p class="mt-1 text-xs text-red-500 flex items-center gap-1">
                                        <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                    </p>
                                @enderror
                                <p class="mt-1 text-xs text-gray-400">{{ $item['hint'] }}</p>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                {{-- Custom Preview Badge --}}
                <div id="customPreviewBadge"
                     class="hidden mt-5 border border-emerald-200 bg-emerald-50 rounded-lg p-3 text-sm text-emerald-800">
                    <p class="font-semibold text-emerald-700 mb-2 flex items-center gap-2">
                        <i class="fas fa-check-circle"></i> Preview Row
                    </p>
                    <div class="font-mono text-xs bg-white border border-emerald-100 rounded p-2" id="customPreviewText">
                        –
                    </div>
                    <p class="mt-2 text-xs text-emerald-600">
                        <i class="fas fa-info-circle"></i>
                        Nilai <strong>0</strong> = ALL (mencakup seluruh sub-level)
                    </p>
                </div>

            </div>{{-- end sectionCustom --}}

            {{-- SUBMIT --}}
            <div class="flex justify-end pt-4 border-t border-gray-100 mt-6">
                <button type="submit" id="btnSubmit"
                    class="bg-sky-600 hover:bg-sky-700 disabled:bg-gray-300 disabled:cursor-not-allowed
                           text-white px-6 py-2.5 rounded-md shadow text-sm font-medium
                           flex items-center gap-2 transition-colors"
                    disabled>
                    <i class="fas fa-save" id="btnIcon"></i>
                    <span id="btnLabel">Generate & Simpan</span>
                </button>
            </div>
        </form>

    </div>
</div>

<script>
// ─── Konstanta ───────────────────────────────────────────────────────────────
const quarterColors = [
    'bg-sky-100 text-sky-700',
    'bg-emerald-100 text-emerald-700',
    'bg-amber-100 text-amber-700',
    'bg-rose-100 text-rose-700',
];
const quarterMonths = [[1,2,3],[4,5,6],[7,8,9],[10,11,12]];

// ─── Element refs ─────────────────────────────────────────────────────────────
const radios         = document.querySelectorAll('input[name="mode"]');
const sectionFull    = document.getElementById('sectionFullYear');
const sectionCustom  = document.getElementById('sectionCustom');
const inputTahun     = document.getElementById('inputTahun');
const previewCard    = document.getElementById('previewCard');
const btnSubmit      = document.getElementById('btnSubmit');
const btnLabel       = document.getElementById('btnLabel');
const btnIcon        = document.getElementById('btnIcon');

// Custom fields (ordered by hierarchy)
const customFields = [
    document.getElementById('custom_decade'),
    document.getElementById('custom_year'),
    document.getElementById('custom_quarter'),
    document.getElementById('custom_month'),
    document.getElementById('custom_day'),
];
const fieldNames = ['decade', 'year', 'quarter', 'month', 'day'];

// ─── Mode switching ───────────────────────────────────────────────────────────
function getMode() {
    return document.querySelector('input[name="mode"]:checked')?.value ?? 'full_year';
}

function switchMode(mode) {
    if (mode === 'full_year') {
        sectionFull.classList.remove('hidden');
        sectionCustom.classList.add('hidden');
        btnLabel.textContent = 'Generate & Simpan';
        btnIcon.className    = 'fas fa-cogs';
        updateFullYearPreview();
    } else {
        sectionFull.classList.add('hidden');
        sectionCustom.classList.remove('hidden');
        btnLabel.textContent = 'Simpan Row';
        btnIcon.className    = 'fas fa-save';
        previewCard.classList.add('hidden');
        updateCustomPreview();
    }
}

radios.forEach(r => r.addEventListener('change', () => switchMode(getMode())));

// ─── Full Year: Preview ───────────────────────────────────────────────────────
function isLeapYear(year) {
    return (year % 4 === 0 && year % 100 !== 0) || (year % 400 === 0);
}
function daysInMonth(year, month) {
    return new Date(year, month, 0).getDate();
}

function updateFullYearPreview() {
    const year = parseInt(inputTahun.value);

    if (!year || year < 1900 || year > 2100) {
        previewCard.classList.add('hidden');
        btnSubmit.disabled = true;
        return;
    }

    const leap      = isLeapYear(year);
    const totalDays = leap ? 366 : 365;
    const decade    = Math.floor(year / 10) * 10;

    document.getElementById('prevTahun').textContent     = year;
    document.getElementById('prevTotalHari').textContent = totalDays + ' hari';
    document.getElementById('prevDekade').textContent    = decade + 'an';
    document.getElementById('prevKabisat').textContent   = leap ? '✅ Ya' : '❌ Tidak';

    const quartersEl = document.getElementById('prevQuarters');
    quartersEl.innerHTML = '';
    quarterMonths.forEach((months, i) => {
        const days = months.reduce((sum, m) => sum + daysInMonth(year, m), 0);
        quartersEl.innerHTML += `
            <div class="rounded-md px-2 py-1.5 text-center ${quarterColors[i]}">
                <p class="text-xs font-semibold">Q${i + 1}</p>
                <p class="text-sm font-bold">${days}</p>
                <p class="text-xs opacity-70">hari</p>
            </div>`;
    });

    previewCard.classList.remove('hidden');
    btnSubmit.disabled = false;
}

inputTahun.addEventListener('input', updateFullYearPreview);

// ─── Custom Hierarki: Preview & Validasi ─────────────────────────────────────
function updateCustomPreview() {
    const badge    = document.getElementById('customPreviewBadge');
    const preText  = document.getElementById('customPreviewText');

    // Ambil nilai; kosong dianggap 0
    const vals = customFields.map(f => f.value.trim() !== '' ? parseInt(f.value) : null);

    // Cek apakah minimal 1 field diisi
    const anyFilled = vals.some(v => v !== null);

    if (!anyFilled) {
        badge.classList.add('hidden');
        btnSubmit.disabled = true;
        return;
    }

    // Temukan level terbawah yang diisi → semua di bawahnya jadi 0
    // Validasi: tidak boleh ada gap (isi level bawah tapi level atas kosong)
    let lastFilledIndex = -1;
    for (let i = 0; i < vals.length; i++) {
        if (vals[i] !== null) lastFilledIndex = i;
    }

    // Cek gap: semua field sebelum lastFilledIndex harus terisi
    let hasGap = false;
    for (let i = 0; i <= lastFilledIndex; i++) {
        if (vals[i] === null) { hasGap = true; break; }
    }

    // Bangun row preview
    const row = fieldNames.map((name, i) => {
        const v = (vals[i] !== null && i <= lastFilledIndex) ? vals[i] : 0;
        return `${name}: <strong>${v === 0 && i > lastFilledIndex ? '<span class="text-gray-400">0 (ALL)</span>' : v}</strong>`;
    });

    preText.innerHTML = row.join(' &nbsp;|&nbsp; ');
    badge.classList.remove('hidden');

    // Disable submit jika ada gap (validasi client-side)
    btnSubmit.disabled = hasGap;
}

customFields.forEach(f => f.addEventListener('input', updateCustomPreview));

// ─── Init ─────────────────────────────────────────────────────────────────────
switchMode(getMode());

// Trigger jika ada old value setelah validation error
if (inputTahun.value) updateFullYearPreview();
if (customFields.some(f => f.value)) updateCustomPreview();
</script>
@endsection