@extends('layouts.main')

@section('content')
<div class="py-6">

    <a href="{{ route('dimensi_waktu.index') }}"
       class="flex items-center gap-1 font-semibold text-sky-600 ps-4 mb-4 hover:text-sky-900 text-sm transition-colors">
        <i class="fas fa-angle-left"></i> Kembali
    </a>

    <div class="mt-2 bg-white rounded-xl shadow-sm border border-gray-100 p-6 max-w-xl mx-auto">

        <h1 class="text-xl font-bold text-gray-800 mb-1">Tambah Dimensi Waktu</h1>
        <p class="text-sm text-gray-400 mb-6">Generate otomatis per level atau input manual satu baris</p>

        @if($errors->any())
            <div class="mb-5 flex items-start gap-3 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
                <i class="fas fa-exclamation-circle text-red-500 mt-0.5 shrink-0"></i>
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
                                    hover:border-sky-300 select-none">
                            <i class="fas fa-calendar-alt block text-lg mb-1"></i>
                            <span class="font-semibold">Generate by Year</span>
                        </div>
                    </label>
                    {{-- <label class="flex-1 cursor-pointer">
                        <input type="radio" name="mode" value="custom" id="modeCustom"
                               class="sr-only peer"
                               {{ old('mode') === 'custom' ? 'checked' : '' }}>
                        <div class="peer-checked:border-sky-500 peer-checked:bg-sky-50 peer-checked:text-sky-700
                                    border border-gray-200 rounded-lg p-3 text-sm text-center transition-all
                                    hover:border-sky-300 select-none">
                            <i class="fas fa-sliders-h block text-lg mb-1"></i>
                            <span class="font-semibold">Custom Time</span>
                        </div>
                    </label> --}}
                </div>
            </div>

            {{-- SECTION A: GENERATE BY YEAR --}}
            <div id="sectionFullYear">

                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        Tahun <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="tahun" id="inputTahun"
                           min="1900" max="2100"
                           value="{{ old('tahun') }}"
                           placeholder="Contoh: {{ date('Y') }}"
                           class="w-full border @error('tahun') border-red-400 @else border-gray-300 @enderror
                                  rounded-lg px-4 py-2.5 text-gray-800 text-sm
                                  focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-transparent transition-shadow"
                           autocomplete="off">
                    @error('tahun')
                        <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                            <i class="fas fa-exclamation-circle"></i> {{ $message }}
                        </p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-400">Rentang tahun yang diperbolehkan: 1900 - 2100</p>
                </div>

                {{-- Stop Level Picker --}}
                <div class="mb-5">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">
                        Generate sampai level <span class="text-red-500">*</span>
                    </label>
                    <p class="text-xs text-gray-400 mb-2">
                        Level di bawah yang dipilih disimpan sebagai <strong>0 (ALL)</strong>.
                    </p>

                    @php
                        $levels = [
                            'decade'   => ['label' => 'Dekade',   'icon' => 'fas fa-layer-group',   'desc' => '1 baris'],
                            'year'     => ['label' => 'Tahun',    'icon' => 'fas fa-calendar',      'desc' => '1 baris'],
                            'semester' => ['label' => 'Semester', 'icon' => 'fas fa-th-large',      'desc' => '2 baris'],
                            'quarter'  => ['label' => 'Kuartal',  'icon' => 'fas fa-th',            'desc' => '4 baris'],
                            'month'    => ['label' => 'Bulan',    'icon' => 'fas fa-calendar-week', 'desc' => '12 baris'],
                        ];
                        $oldLevel = old('stop_level', 'month');
                    @endphp

                    <div class="grid grid-cols-5 gap-2">
                        @foreach($levels as $val => $item)
                        <label class="cursor-pointer">
                            <input type="radio" name="stop_level" value="{{ $val }}"
                                   class="sr-only peer"
                                   {{ $oldLevel === $val ? 'checked' : '' }}>
                            <div class="peer-checked:border-sky-500 peer-checked:bg-sky-50 peer-checked:text-sky-700
                                        border border-gray-200 rounded-lg p-2 text-center
                                        transition-all hover:border-sky-300 h-full flex flex-col items-center justify-center gap-1">
                                <i class="{{ $item['icon'] }} text-gray-400 text-base"></i>
                                <span class="font-semibold text-gray-700 leading-tight text-[11px]">{{ $item['label'] }}</span>
                                <span class="text-gray-400 text-[10px]">{{ $item['desc'] }}</span>
                            </div>
                        </label>
                        @endforeach
                    </div>
                    @error('stop_level')
                        <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                            <i class="fas fa-exclamation-circle"></i> {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Preview Card --}}
                <div id="previewCard"
                     class="hidden mb-5 border border-sky-200 bg-sky-50 rounded-lg p-4 text-sm text-sky-800">
                    <p class="font-semibold text-sky-700 mb-3 flex items-center gap-2">
                        <i class="fas fa-eye"></i> Preview Generate
                    </p>
                    <div class="grid grid-cols-3 gap-y-2 gap-x-4 text-xs">
                        <div>
                            <span class="text-sky-500">Tahun</span>
                            <p id="prevTahun" class="font-bold text-sky-800 text-base">-</p>
                        </div>
                        <div>
                            <span class="text-sky-500">Dekade</span>
                            <p id="prevDekade" class="font-semibold">-</p>
                        </div>
                        <div>
                            <span class="text-sky-500">Jumlah Baris</span>
                            <p id="prevRows" class="font-semibold">-</p>
                        </div>
                    </div>
                    <div class="mt-3 pt-3 border-t border-sky-200">
                        <p class="text-xs text-sky-600 font-medium mb-1">Contoh baris yang akan dibuat:</p>
                        <pre id="prevSample" class="font-mono text-[11px] bg-white border border-sky-100 rounded p-2 whitespace-pre-wrap leading-relaxed"></pre>
                    </div>
                </div>

            </div>

            {{-- SECTION B: CUSTOM HIERARKI --}}
            {{-- <div id="sectionCustom" class="hidden">

                <p class="text-xs text-gray-500 mb-4 bg-gray-50 rounded-lg p-3 border border-gray-100">
                    <i class="fas fa-info-circle text-sky-400 mr-1"></i>
                    Isi dari level teratas (Dekade) secara berurutan. Field yang dibiarkan sebagai
                    <strong>ALL</strong> akan disimpan sebagai <strong>0</strong>.
                    <strong class="text-gray-700">Dekade wajib diisi.</strong>
                </p>

                <div class="space-y-0">

                    <div class="flex items-start gap-3">
                        <div class="flex flex-col items-center pt-2">
                            <div class="w-8 h-8 rounded-full bg-sky-100 flex items-center justify-center shrink-0">
                                <i class="fas fa-layer-group text-sky-500 text-xs"></i>
                            </div>
                            <div class="w-px flex-1 bg-gray-200 mt-1 min-h-6"></div>
                        </div>
                        <div class="flex-1 pb-4">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">
                                Dekade <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="custom_decade" id="custom_decade"
                                   min="1900" max="2100"
                                   value="{{ old('custom_decade') }}"
                                   placeholder="Contoh: 2020"
                                   class="w-full border @error('custom_decade') border-red-400 @else border-gray-300 @enderror
                                          rounded-lg px-3 py-2 text-sm text-gray-800
                                          focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-transparent transition-shadow"
                                   autocomplete="off">
                            @error('custom_decade')
                                <p class="mt-1 text-xs text-red-500 flex items-center gap-1">
                                    <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                </p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-400">Kelipatan 10, misal: 2020, 2010</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <div class="flex flex-col items-center pt-2">
                            <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center shrink-0">
                                <i class="fas fa-calendar text-gray-400 text-xs"></i>
                            </div>
                            <div class="w-px flex-1 bg-gray-200 mt-1 min-h-6"></div>
                        </div>
                        <div class="flex-1 pb-4">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Tahun</label>
                            <input type="number" name="custom_year" id="custom_year"
                                   min="1900" max="2100"
                                   value="{{ old('custom_year') }}"
                                   placeholder="Kosong = ALL (0)"
                                   class="w-full border @error('custom_year') border-red-400 @else border-gray-300 @enderror
                                          rounded-lg px-3 py-2 text-sm text-gray-800
                                          focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-transparent transition-shadow"
                                   autocomplete="off">
                            @error('custom_year')
                                <p class="mt-1 text-xs text-red-500 flex items-center gap-1">
                                    <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                </p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-400">Harus sesuai dengan dekade yang dipilih</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <div class="flex flex-col items-center pt-2">
                            <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center shrink-0">
                                <i class="fas fa-th-large text-gray-400 text-xs"></i>
                            </div>
                            <div class="w-px flex-1 bg-gray-200 mt-1 min-h-6"></div>
                        </div>
                        <div class="flex-1 pb-4">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Semester</label>
                            <select name="custom_semester" id="custom_semester"
                                    class="w-full border @error('custom_semester') border-red-400 @else border-gray-300 @enderror
                                           rounded-lg px-3 py-2 text-sm text-gray-800 bg-white
                                           focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-transparent transition-shadow">
                                <option value="0">-- ALL (semua semester) --</option>
                                <option value="1" {{ old('custom_semester') == 1 ? 'selected' : '' }}>Semester 1 (Januari - Juni)</option>
                                <option value="2" {{ old('custom_semester') == 2 ? 'selected' : '' }}>Semester 2 (Juli - Desember)</option>
                            </select>
                            @error('custom_semester')
                                <p class="mt-1 text-xs text-red-500 flex items-center gap-1">
                                    <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                </p>
                            @enderror
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <div class="flex flex-col items-center pt-2">
                            <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center shrink-0">
                                <i class="fas fa-th text-gray-400 text-xs"></i>
                            </div>
                            <div class="w-px flex-1 bg-gray-200 mt-1 min-h-6"></div>
                        </div>
                        <div class="flex-1 pb-4">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Kuartal</label>
                            <select name="custom_quarter" id="custom_quarter"
                                    class="w-full border @error('custom_quarter') border-red-400 @else border-gray-300 @enderror
                                           rounded-lg px-3 py-2 text-sm text-gray-800 bg-white
                                           focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-transparent transition-shadow">
                                <option value="0">-- ALL (semua kuartal) --</option>
                                <option value="1" data-sem="1" {{ old('custom_quarter') == 1 ? 'selected' : '' }}>Q1 (Januari - Maret)</option>
                                <option value="2" data-sem="1" {{ old('custom_quarter') == 2 ? 'selected' : '' }}>Q2 (April - Juni)</option>
                                <option value="3" data-sem="2" {{ old('custom_quarter') == 3 ? 'selected' : '' }}>Q3 (Juli - September)</option>
                                <option value="4" data-sem="2" {{ old('custom_quarter') == 4 ? 'selected' : '' }}>Q4 (Oktober - Desember)</option>
                            </select>
                            @error('custom_quarter')
                                <p class="mt-1 text-xs text-red-500 flex items-center gap-1">
                                    <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                </p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-400">Difilter otomatis berdasarkan semester</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <div class="flex flex-col items-center pt-2">
                            <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center shrink-0">
                                <i class="fas fa-calendar-week text-gray-400 text-xs"></i>
                            </div>
                        </div>
                        <div class="flex-1 pb-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Bulan</label>
                            <select name="custom_month" id="custom_month"
                                    class="w-full border @error('custom_month') border-red-400 @else border-gray-300 @enderror
                                           rounded-lg px-3 py-2 text-sm text-gray-800 bg-white
                                           focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-transparent transition-shadow">
                                <option value="0">-- ALL (semua bulan) --</option>
                                @php
                                    $bulanData = [
                                        1  => ['name' => 'Januari',   'q' => 1, 's' => 1],
                                        2  => ['name' => 'Februari',  'q' => 1, 's' => 1],
                                        3  => ['name' => 'Maret',     'q' => 1, 's' => 1],
                                        4  => ['name' => 'April',     'q' => 2, 's' => 1],
                                        5  => ['name' => 'Mei',       'q' => 2, 's' => 1],
                                        6  => ['name' => 'Juni',      'q' => 2, 's' => 1],
                                        7  => ['name' => 'Juli',      'q' => 3, 's' => 2],
                                        8  => ['name' => 'Agustus',   'q' => 3, 's' => 2],
                                        9  => ['name' => 'September', 'q' => 3, 's' => 2],
                                        10 => ['name' => 'Oktober',   'q' => 4, 's' => 2],
                                        11 => ['name' => 'November',  'q' => 4, 's' => 2],
                                        12 => ['name' => 'Desember',  'q' => 4, 's' => 2],
                                    ];
                                @endphp
                                @foreach($bulanData as $num => $b)
                                    <option value="{{ $num }}"
                                            data-q="{{ $b['q'] }}"
                                            data-sem="{{ $b['s'] }}"
                                            {{ old('custom_month') == $num ? 'selected' : '' }}>
                                        {{ $b['name'] }} ({{ $num }})
                                    </option>
                                @endforeach
                            </select>
                            @error('custom_month')
                                <p class="mt-1 text-xs text-red-500 flex items-center gap-1">
                                    <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                </p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-400">Difilter otomatis berdasarkan kuartal dan semester</p>
                        </div>
                    </div>

                </div>

                <div id="customPreviewBadge"
                     class="hidden mt-5 border border-emerald-200 bg-emerald-50 rounded-lg p-3 text-sm text-emerald-800">
                    <p class="font-semibold text-emerald-700 mb-2 flex items-center gap-2">
                        <i class="fas fa-check-circle"></i> Preview Baris
                    </p>
                    <div class="font-mono text-[11px] bg-white border border-emerald-100 rounded p-2 leading-relaxed"
                         id="customPreviewText">-</div>
                    <p class="mt-2 text-xs text-emerald-600">
                        <i class="fas fa-info-circle"></i>
                        Nilai <strong>0</strong> = ALL (mencakup seluruh sub-level)
                    </p>
                </div>

            </div> --}}

            <div class="flex justify-end pt-4 border-t border-gray-100 mt-6">
                <button type="submit" id="btnSubmit"
                    class="bg-sky-600 hover:bg-sky-700 disabled:bg-gray-300 disabled:cursor-not-allowed
                           text-white px-6 py-2.5 rounded-lg shadow-sm text-sm font-medium
                           flex items-center gap-2 transition-colors"
                    disabled>
                    <i class="fas fa-save" id="btnIcon"></i>
                    <span id="btnLabel">Generate &amp; Simpan</span>
                </button>
            </div>
        </form>

    </div>
</div>

<script>
const LEVEL_ROWS = { decade: 1, year: 1, semester: 2, quarter: 4, month: 12 };
const MONTH_NAMES = ['','Januari','Februari','Maret','April','Mei','Juni',
                     'Juli','Agustus','September','Oktober','November','Desember'];

const radios      = document.querySelectorAll('input[name="mode"]');
const sectionFull = document.getElementById('sectionFullYear');
const sectionCust = document.getElementById('sectionCustom');
const inputTahun  = document.getElementById('inputTahun');
const previewCard = document.getElementById('previewCard');
const btnSubmit   = document.getElementById('btnSubmit');
const btnLabel    = document.getElementById('btnLabel');
const btnIcon     = document.getElementById('btnIcon');

const inputDecade  = document.getElementById('custom_decade');
const inputYear    = document.getElementById('custom_year');
const selSemester  = document.getElementById('custom_semester');
const selQuarter   = document.getElementById('custom_quarter');
const selMonth     = document.getElementById('custom_month');

// ── Mode switching ────────────────────────────────────────────────────────────
function getMode() {
    return document.querySelector('input[name="mode"]:checked')?.value ?? 'full_year';
}

function switchMode(mode) {
    if (mode === 'full_year') {
        sectionFull.classList.remove('hidden');
        sectionCust.classList.add('hidden');
        btnLabel.textContent = 'Generate & Simpan';
        btnIcon.className    = 'fas fa-cogs';
        updateFullYearPreview();
    } else {
        sectionFull.classList.add('hidden');
        sectionCust.classList.remove('hidden');
        btnLabel.textContent = 'Simpan Baris';
        btnIcon.className    = 'fas fa-save';
        updateCustomPreview();
    }
}

radios.forEach(r => r.addEventListener('change', () => switchMode(getMode())));

// ── Full Year preview ─────────────────────────────────────────────────────────
function getSelectedLevel() {
    return document.querySelector('input[name="stop_level"]:checked')?.value ?? 'month';
}

function buildSampleText(decade, year, level) {
    const d = decade, y = year;
    const map = {
        decade:   `decade=${d} | year=0 | semester=0 | quarter=0 | month=0`,
        year:     `decade=${d} | year=${y} | semester=0 | quarter=0 | month=0`,
        semester: `decade=${d} | year=${y} | semester=1 | quarter=0 | month=0\ndecade=${d} | year=${y} | semester=2 | quarter=0 | month=0`,
        quarter:  `decade=${d} | year=${y} | semester=1 | quarter=1 | month=0\ndecade=${d} | year=${y} | semester=1 | quarter=2 | month=0\ndecade=${d} | year=${y} | semester=2 | quarter=3 | month=0\ndecade=${d} | year=${y} | semester=2 | quarter=4 | month=0`,
        month:    `decade=${d} | year=${y} | semester=1 | quarter=1 | month=1  (Januari)\ndecade=${d} | year=${y} | semester=1 | quarter=1 | month=2  (Februari)\n... hingga ...\ndecade=${d} | year=${y} | semester=2 | quarter=4 | month=12 (Desember)`,
    };
    return map[level] ?? '';
}

function updateFullYearPreview() {
    const year  = parseInt(inputTahun.value);
    const level = getSelectedLevel();

    if (!year || year < 1900 || year > 2100) {
        previewCard.classList.add('hidden');
        btnSubmit.disabled = true;
        return;
    }

    const decade = Math.floor(year / 10) * 10;
    document.getElementById('prevTahun').textContent  = year;
    document.getElementById('prevDekade').textContent = decade + 'an';
    document.getElementById('prevRows').textContent   = LEVEL_ROWS[level] + ' baris';
    document.getElementById('prevSample').textContent = buildSampleText(decade, year, level);

    previewCard.classList.remove('hidden');
    btnSubmit.disabled = false;
}

inputTahun.addEventListener('input', updateFullYearPreview);
document.querySelectorAll('input[name="stop_level"]').forEach(r =>
    r.addEventListener('change', updateFullYearPreview)
);

// ── Cascading dropdowns ───────────────────────────────────────────────────────
function filterQuarters(semVal) {
    const opts       = selQuarter.querySelectorAll('option[data-sem]');
    const currentVal = selQuarter.value;

    opts.forEach(opt => {
        const belongs = semVal == 0 || opt.dataset.sem == semVal;
        opt.hidden    = !belongs;
        opt.disabled  = !belongs;
    });

    const stillValid = selQuarter.querySelector(`option[value="${currentVal}"]:not([disabled])`);
    if (!stillValid && currentVal !== '0') selQuarter.value = '0';

    filterMonths(selQuarter.value, semVal);
}

function filterMonths(qVal, semVal) {
    const opts       = selMonth.querySelectorAll('option[data-q]');
    const currentVal = selMonth.value;

    opts.forEach(opt => {
        const qOk    = qVal   == 0 || opt.dataset.q   == qVal;
        const semOk  = semVal == 0 || opt.dataset.sem == semVal;
        opt.hidden   = !(qOk && semOk);
        opt.disabled = !(qOk && semOk);
    });

    const stillValid = selMonth.querySelector(`option[value="${currentVal}"]:not([disabled])`);
    if (!stillValid && currentVal !== '0') selMonth.value = '0';
}

// selSemester.addEventListener('change', () => { filterQuarters(selSemester.value); updateCustomPreview(); });
// selQuarter.addEventListener('change',  () => { filterMonths(selQuarter.value, selSemester.value); updateCustomPreview(); });
// selMonth.addEventListener('change',    updateCustomPreview);
// inputDecade.addEventListener('input',  updateCustomPreview);
// inputYear.addEventListener('input',    updateCustomPreview);

// ── Custom preview ────────────────────────────────────────────────────────────
// function updateCustomPreview() {
//     const badge   = document.getElementById('customPreviewBadge');
//     const preText = document.getElementById('customPreviewText');

//     if (!inputDecade.value.trim()) {
//         badge.classList.add('hidden');
//         btnSubmit.disabled = true;
//         return;
//     }

//     const decade   = parseInt(inputDecade.value);
//     const year     = inputYear.value.trim() ? parseInt(inputYear.value) : 0;
//     const semester = parseInt(selSemester.value);
//     const quarter  = parseInt(selQuarter.value);
//     const month    = parseInt(selMonth.value);

//     const fmt = (label, val, display) => {
//         const d = display !== undefined ? display : (val === 0 ? 'ALL' : val);
//         const cls = val === 0 ? 'color:#9ca3af' : 'color:#059669;font-weight:700';
//         return `${label}: <span style="${cls}">${d}</span>`;
//     };

//     preText.innerHTML = [
//         fmt('decade',   decade,   decade),
//         fmt('year',     year),
//         fmt('semester', semester),
//         fmt('quarter',  quarter),
//         fmt('month',    month,    month > 0 ? MONTH_NAMES[month] : 'ALL'),
//     ].join(' <span style="color:#d1d5db"> | </span> ');

//     badge.classList.remove('hidden');
//     btnSubmit.disabled = false;
// }

// ── Init ──────────────────────────────────────────────────────────────────────
switchMode(getMode());
filterQuarters(selSemester.value);
if (inputTahun.value)  updateFullYearPreview();
// if (inputDecade.value) updateCustomPreview();
</script>
@endsection