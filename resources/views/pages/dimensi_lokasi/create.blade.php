@extends('layouts.main')

@section('content')
<div class="py-6">

    <a href="{{ route('dimensi_lokasi.index') }}"
       class="flex items-center gap-1 font-semibold text-sky-600 ps-4 mb-4 hover:text-sky-900 text-sm transition-colors">
        <i class="fas fa-angle-left"></i> Kembali
    </a>

    {{-- WARNING DUPLIKASI --}}
    @if(session('warning'))
        <div class="flex gap-3 bg-yellow-50 border border-yellow-300 text-yellow-800 px-4 py-4 rounded-lg shadow-sm mb-4">
            <i class="fas fa-exclamation-triangle mt-0.5 text-yellow-500"></i>
            <span class="text-sm">{{ session('warning') }}</span>
        </div>
    @endif

    <div class="mt-2 bg-white rounded-md shadow p-6 max-w-xl mx-auto">

        <h1 class="text-xl font-bold text-gray-800 mb-6">Tambah Lokasi</h1>

        <form action="{{ route('dimensi_lokasi.store') }}" method="POST" class="space-y-5">
            @csrf

            {{-- PROVINSI --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Provinsi</label>
                <input type="text" value="BALI"
                       class="w-full border border-gray-200 rounded-md px-3 py-2.5 bg-gray-50 text-gray-500 text-sm"
                       readonly>
            </div>

            {{-- KABUPATEN --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Kabupaten/Kota 
                </label>
                <div class="relative">
                    <select id="kabupaten" name="kabupaten"
                            class="w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm text-gray-800
                                   focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-transparent
                                   disabled:bg-gray-50 disabled:text-gray-400 transition-shadow appearance-none"
                            disabled>
                        <option value="">Memuat data...</option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center gap-2">
                        <span id="spin_kabupaten" class="hidden">
                            <svg class="animate-spin h-4 w-4 text-sky-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                            </svg>
                        </span>
                        <i id="chevron_kabupaten" class="fas fa-chevron-down text-gray-400 text-xs"></i>
                    </div>
                </div>
                <p id="hint_kabupaten" class="mt-1 text-xs text-sky-500 hidden">
                    <i class="fas fa-circle-notch fa-spin mr-1"></i> Memuat daftar kabupaten...
                </p>
            </div>

            {{-- KECAMATAN --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Kecamatan 
                </label>
                <div class="relative">
                    <select id="kecamatan" name="kecamatan"
                            class="w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm text-gray-800
                                   focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-transparent
                                   disabled:bg-gray-50 disabled:text-gray-400 transition-shadow appearance-none"
                            disabled>
                        <option value="">Pilih Kabupaten dulu</option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center gap-2">
                        <span id="spin_kecamatan" class="hidden">
                            <svg class="animate-spin h-4 w-4 text-sky-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                            </svg>
                        </span>
                        <i id="chevron_kecamatan" class="fas fa-chevron-down text-gray-400 text-xs"></i>
                    </div>
                </div>
                <p id="hint_kecamatan" class="mt-1 text-xs text-sky-500 hidden">
                    <i class="fas fa-circle-notch fa-spin mr-1"></i> Memuat daftar kecamatan...
                </p>
            </div>

            {{-- DESA --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Desa 
                </label>
                <div class="relative">
                    <select id="desa" name="desa"
                            class="w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm text-gray-800
                                   focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-transparent
                                   disabled:bg-gray-50 disabled:text-gray-400 transition-shadow appearance-none"
                            disabled>
                        <option value="">Pilih Kecamatan dulu</option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center gap-2">
                        <span id="spin_desa" class="hidden">
                            <svg class="animate-spin h-4 w-4 text-sky-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                            </svg>
                        </span>
                        <i id="chevron_desa" class="fas fa-chevron-down text-gray-400 text-xs"></i>
                    </div>
                </div>
                <p id="hint_desa" class="mt-1 text-xs text-sky-500 hidden">
                    <i class="fas fa-circle-notch fa-spin mr-1"></i> Memuat daftar desa...
                </p>
            </div>

            {{-- Hidden fields --}}
            <input type="hidden" name="kode_provinsi" value="51">
            <input type="hidden" name="kode_kabupaten" id="kode_kabupaten">
            <input type="hidden" name="kode_kecamatan" id="kode_kecamatan">
            <input type="hidden" name="kode_desa"      id="kode_desa">

            <input type="hidden" name="nama_kabupaten" id="nama_kabupaten">
            <input type="hidden" name="nama_kecamatan" id="nama_kecamatan">
            <input type="hidden" name="nama_desa"      id="nama_desa">

            {{-- SUBMIT --}}
            <div class="flex justify-end pt-2 border-t border-gray-100">
                <button type="submit"
                        class="bg-sky-600 hover:bg-sky-700 text-white px-6 py-2.5 rounded-md shadow
                               text-sm font-medium flex items-center gap-2 transition-colors">
                    <i class="fas fa-save"></i> Simpan
                </button>
            </div>

        </form>
    </div>
</div>

<script>

function debounce(func, delay) {
    let timeout;
    return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), delay);
    };
}

function setLoading(id, on, placeholder = 'Pilih...') {
    const sel     = document.getElementById(id);
    const spin    = document.getElementById(`spin_${id}`);
    const chevron = document.getElementById(`chevron_${id}`);
    const hint    = document.getElementById(`hint_${id}`);

    if (on) {
        sel.disabled = true;
        sel.innerHTML = `<option value="">Memuat data...</option>`;
        spin.classList.remove('hidden');
        chevron.classList.add('hidden');
        hint.classList.remove('hidden');
    } else {
        sel.disabled = false;
        spin.classList.add('hidden');
        chevron.classList.remove('hidden');
        hint.classList.add('hidden');
        if (sel.options[0]?.value !== '') {
            sel.options[0].textContent = placeholder;
        }
    }
}

function resetSelect(id, placeholder) {
    const sel = document.getElementById(id);
    sel.innerHTML = `<option value="">${placeholder}</option>`;
    sel.disabled = true;
    document.getElementById(`spin_${id}`).classList.add('hidden');
    document.getElementById(`chevron_${id}`).classList.remove('hidden');
    document.getElementById(`hint_${id}`).classList.add('hidden');
}

// ─── Init: load Kabupaten on page ready ──────────────────────────────────────

document.addEventListener('DOMContentLoaded', function () {

    setLoading('kabupaten', true);

    fetch('/api/bali/kabupaten')
        .then(res => {
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            return res.json();
        })
        .then(data => {
            const kab = document.getElementById('kabupaten');
            kab.innerHTML = '<option value="">Pilih Kabupaten</option>';
            data.forEach(item => {
                kab.innerHTML += `<option value="${item.nama}" data-kode="${item.kode}">${item.nama}</option>`;
            });
            setLoading('kabupaten', false, 'Pilih Kabupaten');
        })
        .catch(() => {
            const kab = document.getElementById('kabupaten');
            kab.innerHTML = '<option value="">Gagal memuat — coba refresh</option>';
            setLoading('kabupaten', false);
        });

});

// ─── Kabupaten → load Kecamatan ───────────────────────────────────────────────

document.getElementById('kabupaten').addEventListener('change', debounce(function () {
    const selected = this.options[this.selectedIndex];
    const kodeKab  = selected.getAttribute('data-kode');

    // Reset downstream
    resetSelect('kecamatan', 'Pilih Kabupaten dulu');
    resetSelect('desa', 'Pilih Kecamatan dulu');
    document.getElementById('kode_kabupaten').value = '';
    document.getElementById('kode_kecamatan').value = '';
    document.getElementById('kode_desa').value      = '';
    document.getElementById('nama_kabupaten').value = selected.value ?? '';
    document.getElementById('nama_kecamatan').value = '';
    document.getElementById('nama_desa').value      = '';

    if (!kodeKab) return;

    document.getElementById('kode_kabupaten').value = kodeKab;
    setLoading('kecamatan', true);

    fetch(`/api/bali/kecamatan?kab=${kodeKab}`)
        .then(res => {
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            return res.json();
        })
        .then(data => {
            const kec = document.getElementById('kecamatan');
            kec.innerHTML = '<option value="">Pilih Kecamatan</option>';
            data.forEach(item => {
                kec.innerHTML += `<option value="${item.nama}" data-kode="${item.kode}">${item.nama}</option>`;
            });
            setLoading('kecamatan', false, 'Pilih Kecamatan');
        })
        .catch(() => {
            const kec = document.getElementById('kecamatan');
            kec.innerHTML = '<option value="">Gagal memuat — coba lagi</option>';
            setLoading('kecamatan', false);
        });

}, 300));

// ─── Kecamatan → load Desa ────────────────────────────────────────────────────

document.getElementById('kecamatan').addEventListener('change', debounce(function () {
    // BUG FIX: nama variabel diubah dari 'selected' → 'selectedKec' (konsisten)
    const selectedKec = this.options[this.selectedIndex];
    const kodeKec     = selectedKec.getAttribute('data-kode');
    const kodeKab     = document.getElementById('kabupaten').selectedOptions[0]?.getAttribute('data-kode');

    // Reset downstream
    resetSelect('desa', 'Pilih Kecamatan dulu');
    document.getElementById('kode_kecamatan').value = '';
    document.getElementById('kode_desa').value      = '';
    // BUG FIX: 'selected' → 'selectedKec'
    document.getElementById('nama_kecamatan').value = selectedKec.value ?? '';
    document.getElementById('nama_desa').value      = '';

    if (!kodeKec || !kodeKab) return;

    document.getElementById('kode_kecamatan').value = kodeKec;
    setLoading('desa', true);

    fetch(`/api/bali/desa?kab=${kodeKab}&kec=${kodeKec}`)
        .then(res => {
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            return res.json();
        })
        .then(data => {
            const desa = document.getElementById('desa');
            desa.innerHTML = '<option value="">Pilih Desa</option>';
            data.forEach(item => {
                desa.innerHTML += `<option value="${item.nama}" data-kode="${item.kode}">${item.nama}</option>`;
            });
            setLoading('desa', false, 'Pilih Desa');
        })
        .catch(() => {
            const desa = document.getElementById('desa');
            desa.innerHTML = '<option value="">Gagal memuat — coba lagi</option>';
            setLoading('desa', false);
        });

}, 300));

// ─── Desa → simpan kode & nama ────────────────────────────────────────────────

document.getElementById('desa').addEventListener('change', debounce(function () {
    const selected = this.options[this.selectedIndex];
    document.getElementById('kode_desa').value = selected.getAttribute('data-kode') ?? '';
    document.getElementById('nama_desa').value = selected.value ?? '';
}, 300));

</script>

@endsection