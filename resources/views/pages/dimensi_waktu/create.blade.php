@extends('layouts.main')

@section('content')
<div class="py-6">

    <a href="{{ route('dimensi_waktu.index') }}"
       class="flex items-center gap-1 font-semibold text-sky-600 ps-4 mb-4 hover:text-sky-900 text-sm transition-colors">
        <i class="fas fa-angle-left"></i> Kembali
    </a>

    <div class="mt-2 bg-white rounded-md shadow p-6 max-w-xl mx-auto">

        <h1 class="text-xl font-bold text-gray-800 mb-1">Tambah Dimensi Waktu</h1>
        <p class="text-sm text-gray-400 mb-6">Generate otomatis seluruh hari dalam satu tahun</p>

        {{-- ERROR ALERT --}}
        @if($errors->any())
            <div class="mb-5 flex items-start gap-3 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
                <i class="fas fa-exclamation-circle text-red-500 mt-0.5"></i>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <form action="{{ route('dimensi_waktu.store') }}" method="POST" id="formTambahWaktu">
            @csrf

            {{-- INPUT TAHUN --}}
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
                    required
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

                {{-- Quarter breakdown --}}
                <div class="mt-3 pt-3 border-t border-sky-200">
                    <p class="text-xs text-sky-500 mb-2 font-medium">Distribusi per Kuartal</p>
                    <div class="grid grid-cols-4 gap-2" id="prevQuarters"></div>
                </div>
            </div>

            {{-- SUBMIT --}}
            <div class="flex justify-end pt-2">
                <button type="submit" id="btnSubmit"
                    class="bg-sky-600 hover:bg-sky-700 disabled:bg-gray-300 disabled:cursor-not-allowed
                           text-white px-6 py-2.5 rounded-md shadow text-sm font-medium
                           flex items-center gap-2 transition-colors"
                    disabled>
                    <i class="fas fa-cogs"></i>
                    Generate & Simpan
                </button>
            </div>
        </form>

    </div>
</div>

<script>
    const input     = document.getElementById('inputTahun');
    const preview   = document.getElementById('previewCard');
    const btnSubmit = document.getElementById('btnSubmit');

    const quarterColors = [
        'bg-sky-100 text-sky-700',
        'bg-emerald-100 text-emerald-700',
        'bg-amber-100 text-amber-700',
        'bg-rose-100 text-rose-700',
    ];

    // Hari per bulan per kuartal
    const quarterMonths = [
        [1, 2, 3],
        [4, 5, 6],
        [7, 8, 9],
        [10, 11, 12],
    ];

    function isLeapYear(year) {
        return (year % 4 === 0 && year % 100 !== 0) || (year % 400 === 0);
    }

    function daysInMonth(year, month) {
        return new Date(year, month, 0).getDate();
    }

    function updatePreview() {
        const year = parseInt(input.value);

        if (!year || year < 1900 || year > 2100) {
            preview.classList.add('hidden');
            btnSubmit.disabled = true;
            return;
        }

        const leap      = isLeapYear(year);
        const totalDays = leap ? 366 : 365;
        const decade    = Math.floor(year / 10) * 10;

        document.getElementById('prevTahun').textContent    = year;
        document.getElementById('prevTotalHari').textContent = totalDays + ' hari';
        document.getElementById('prevDekade').textContent   = decade + 'an';
        document.getElementById('prevKabisat').textContent  = leap ? '✅ Ya' : '❌ Tidak';

        // Quarter breakdown
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

        preview.classList.remove('hidden');
        btnSubmit.disabled = false;
    }

    input.addEventListener('input', updatePreview);

    // Trigger jika ada old value (setelah validation error)
    if (input.value) updatePreview();
</script>
@endsection