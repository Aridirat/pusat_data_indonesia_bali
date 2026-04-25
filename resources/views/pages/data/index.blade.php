@extends('layouts.main')

@section('content')

@php
    $activeMetadataId   = request('metadata_id', '');
    $activeWilayahId    = request('filter_wilayah_id', '');
    $activeWilayah      = request('nama_wilayah', '');
    $activeYear         = request('year', '');
    $activeSearch       = request('search', '');
    $activeTemplateId   = request('template_id', '');
    $activeMetadataNama = $metadataList->firstWhere('metadata_id', $activeMetadataId)?->nama ?? '';

    // Ambil nama wilayah untuk display badge dari DB jika ada ID-nya
    if ($activeWilayahId && !$activeWilayah) {
        $activeWilayah = \App\Models\Location::find($activeWilayahId)?->nama_wilayah ?? '';
    }
@endphp

<div class="mt-2 bg-white rounded-xl shadow p-6">

    {{-- HEADER --}}
    <div class="flex justify-between items-start">
        <div>
            <h1 class="text-xl font-bold text-gray-800">Halamana Data</h1>
            <p class="text-sm text-gray-400 mt-1">Menyajikan data sesuai dengan kebutuhan Anda</p>
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
    <div>
        <div class="flex flex-col justify-between items-start my-5 gap-3">
            <div>
                <h2 class="text-lg font-bold text-gray-800">
                    Kelola Data
                </h2>
            </div>
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
        </div>
    </div>
</div>
    
@include('pages.template._template-panel')

{{-- ═══ MODAL SIMPAN TEMPLATE ═══ --}}
<div id="modalTemplate"
    class="fixed inset-0 z-50 hidden flex items-center justify-center p-4"
    style="background:rgba(0,0,0,0.45);">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg max-h-[90vh] flex flex-col overflow-hidden">
        <div class="px-6 py-4 shrink-0" style="background:linear-gradient(135deg,#8b5cf6,#6d28d9);">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-white font-bold text-base flex items-center gap-2">
                        <i class="fas fa-bookmark"></i> Simpan Template
                    </h3>
                    <p class="text-purple-200 text-xs mt-0.5">Menyimpan filter aktif beserta data yang dicentang (opsional)</p>
                </div>
                <button onclick="closeTemplateModal()" class="text-purple-200 hover:text-white text-2xl leading-none">×</button>
            </div>
        </div>
        <div class="p-6 overflow-y-auto flex-1 space-y-5">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                    Nama Template <span class="text-red-500">*</span>
                </label>
                <input type="text" id="templateNama" placeholder="cth: Data Penduduk 2023"
                    class="w-full border border-gray-300 rounded-md px-3 py-2.5 text-sm
                           focus:outline-none focus:ring-2 focus:ring-purple-400">
                <p id="templateNamaError" class="hidden mt-1 text-xs text-red-500">
                    <i class="fas fa-exclamation-circle mr-1"></i> Nama template wajib diisi.
                </p>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">
                    <i class="fas fa-filter mr-1"></i> Filter Tersimpan
                </p>
                <div id="modalFilterBadges" class="flex flex-wrap gap-1.5 min-h-6"></div>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2 flex items-center justify-between">
                    Data Terpilih
                    <span id="modalDataCount" class="font-bold px-2 py-0.5 rounded-full text-xs"
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
        <div class="px-6 py-4 border-t bg-gray-50 flex justify-end gap-2 shrink-0">
            <button onclick="closeTemplateModal()"
                class="border border-gray-300 text-gray-500 hover:bg-gray-100 px-4 py-2 rounded-md text-sm transition-colors">
                Batal
            </button>
            <button onclick="submitTemplate()"
                class="px-5 py-2 rounded-md text-sm font-semibold text-white flex items-center gap-2 transition-colors"
                style="background:#8b5cf6;"
                onmouseover="this.style.background='#7c3aed'"
                onmouseout="this.style.background='#8b5cf6'">
                <i class="fas fa-save"></i> Simpan Template
            </button>
        </div>
    </div>
</div>

{{-- Form tersembunyi untuk submit template --}}
<form id="formSaveTemplate" action="{{ route('data.template.store') }}" method="POST" class="hidden">
    @csrf
    <input type="hidden" name="nama_tampilan"       id="formTemplateName">
    <input type="hidden" name="filter_metadata_id"  id="formFilterMetadataId"  value="{{ $activeMetadataId }}">
    <input type="hidden" name="filter_wilayah_id"   id="formFilterWilayahId"   value="{{ $activeWilayahId }}">
    <input type="hidden" name="filter_year"         id="formFilterYear"        value="{{ $activeYear }}">
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

// ────────────────────────────────────────────────────────────────
// HELPER — baca state filter aktif
// ────────────────────────────────────────────────────────────────
function getActiveFilter() {
    return {
        metadataId:   document.getElementById('metadataId').value.trim(),
        metadataNama: document.getElementById('metadataSearch').value.trim(),
        wilayahId:    document.getElementById('wilayahId').value.trim(),
        wilayahNama:  document.getElementById('wilayahSearch').value.trim(),
        year:         document.getElementById('yearSearch').value.trim(),
    };
}

function hideSuggestions(id) {
    document.getElementById(id)?.classList.add('hidden');
}

function onFilterChange() {
    const f = getActiveFilter();
    const hasAny = f.metadataId || f.wilayahId || f.year;

    document.getElementById('btnApplyFilter').classList.toggle('hidden', !hasAny);
    document.getElementById('btnSaveTemplate').classList.toggle('hidden', !hasAny);

    updateSelectionUI();
}

// ════════════════════════════════════════════════════════════════
// METADATA DROPDOWN
// ════════════════════════════════════════════════════════════════
const metadataSearchUrl = '{{ route("data.search_metadata") }}';
let metadataTimeout = null;
let metadataAllCache = null;

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
                last:border-0 transition-colors ${isSelected ? 'bg-sky-50 hover:bg-sky-100' : 'hover:bg-gray-50'}">
            <span class="mt-0.5 shrink-0 w-4 h-4 flex items-center justify-center">
                ${isSelected
                    ? '<i class="fas fa-check text-sky-500 text-xs"></i>'
                    : '<i class="fas fa-database text-gray-300 text-xs"></i>'}
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

function onMetadataFocus() {
    const box = document.getElementById('metadataSuggestions');
    if (!box.classList.contains('hidden')) return;
    if (metadataAllCache) { renderMetadataSuggestions(metadataAllCache); return; }

    box.innerHTML = '<p class="px-4 py-3 text-xs text-gray-400 text-center"><i class="fas fa-circle-notch fa-spin mr-1"></i>Memuat...</p>';
    box.classList.remove('hidden');

    fetch(`${metadataSearchUrl}?q=`)
        .then(r => r.json())
        .then(results => { metadataAllCache = results; renderMetadataSuggestions(results); })
        .catch(() => { box.innerHTML = '<p class="px-4 py-3 text-xs text-red-400 text-center">Gagal memuat data</p>'; });
}

function onMetadataInput() {
    clearTimeout(metadataTimeout);
    const q = document.getElementById('metadataSearch').value.trim();
    if (q.length === 0) {
        document.getElementById('metadataId').value = '';
        document.getElementById('clearMetadata').classList.add('hidden');
        metadataAllCache ? renderMetadataSuggestions(metadataAllCache) : onMetadataFocus();
        onFilterChange();
        return;
    }
    const box = document.getElementById('metadataSuggestions');
    box.innerHTML = '<p class="px-4 py-3 text-xs text-gray-400 text-center"><i class="fas fa-circle-notch fa-spin mr-1"></i>Mencari...</p>';
    box.classList.remove('hidden');
    metadataTimeout = setTimeout(() => {
        fetch(`${metadataSearchUrl}?q=${encodeURIComponent(q)}`)
            .then(r => r.json())
            .then(results => renderMetadataSuggestions(results))
            .catch(() => { box.innerHTML = '<p class="px-4 py-3 text-xs text-red-400 text-center">Gagal memuat</p>'; });
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
// WILAYAH DROPDOWN
// ════════════════════════════════════════════════════════════════
const wilayahSearchUrl = '{{ route("data.search_wilayah") }}';
let wilayahTimeout = null;
let wilayahCache   = null;

function renderWilayahSuggestions(results) {
    const box = document.getElementById('wilayahSuggestions');
    const currentId = document.getElementById('wilayahId').value;

    if (results.length === 0) {
        box.innerHTML = '<p class="px-4 py-3 text-xs text-gray-400 text-center">Tidak ada hasil</p>';
        box.classList.remove('hidden');
        return;
    }

    box.innerHTML = results.map(w => {
        const isSelected = String(w.id) === String(currentId);
        return `<button type="button"
            onclick="selectWilayah(${w.id}, '${w.path.replace(/'/g,"\\'")}')"
            class="w-full text-left px-4 py-2.5 flex items-center gap-2.5 border-b border-gray-50
                last:border-0 transition-colors ${isSelected ? 'bg-emerald-50 hover:bg-emerald-100' : 'hover:bg-gray-50'}">
            <span class="shrink-0">
                ${isSelected
                    ? '<i class="fas fa-check text-emerald-500 text-xs"></i>'
                    : '<i class="fas fa-map-marker-alt text-gray-300 text-xs"></i>'}
            </span>
            <span class="text-xs ${isSelected ? 'font-semibold text-emerald-700' : 'text-gray-700'}">${w.path}</span>
        </button>`;
    }).join('');

    box.classList.remove('hidden');
}

function onWilayahFocus() {
    const box = document.getElementById('wilayahSuggestions');
    if (!box.classList.contains('hidden')) return;
    if (wilayahCache) { renderWilayahSuggestions(wilayahCache); return; }

    box.innerHTML = '<p class="px-4 py-3 text-xs text-gray-400 text-center"><i class="fas fa-circle-notch fa-spin mr-1"></i>Memuat...</p>';
    box.classList.remove('hidden');

    fetch(`${wilayahSearchUrl}?q=`)
        .then(r => r.json())
        .then(res => { wilayahCache = res; renderWilayahSuggestions(res); })
        .catch(() => { box.innerHTML = '<p class="px-4 py-3 text-xs text-red-400 text-center">Gagal memuat</p>'; });
}

function onWilayahInput() {
    clearTimeout(wilayahTimeout);
    const q = document.getElementById('wilayahSearch').value.trim();
    if (q.length === 0) {
        document.getElementById('wilayahId').value   = '';
        document.getElementById('wilayahNama').value = '';
        document.getElementById('clearWilayah').classList.add('hidden');
        wilayahCache ? renderWilayahSuggestions(wilayahCache) : onWilayahFocus();
        onFilterChange();
        return;
    }

    // Filter dari cache dulu (lebih cepat)
    if (wilayahCache) {
        const filtered = wilayahCache.filter(w => w.path.toLowerCase().includes(q.toLowerCase()));
        renderWilayahSuggestions(filtered);
        return;
    }

    wilayahTimeout = setTimeout(() => {
        fetch(`${wilayahSearchUrl}?q=${encodeURIComponent(q)}`)
            .then(r => r.json())
            .then(renderWilayahSuggestions)
            .catch(() => {});
    }, 250);
}

function selectWilayah(id, nama) {
    document.getElementById('wilayahSearch').value = nama;
    document.getElementById('wilayahId').value     = id;   // → dikirim sebagai filter_wilayah_id
    document.getElementById('wilayahNama').value   = nama; // → dikirim sebagai nama_wilayah
    document.getElementById('clearWilayah').classList.remove('hidden');
    hideSuggestions('wilayahSuggestions');
    onFilterChange();
}

function clearWilayahFilter() {
    document.getElementById('wilayahSearch').value = '';
    document.getElementById('wilayahId').value     = '';
    document.getElementById('wilayahNama').value   = '';
    document.getElementById('clearWilayah').classList.add('hidden');
    hideSuggestions('wilayahSuggestions');
    onFilterChange();
}

// ════════════════════════════════════════════════════════════════
// YEAR DROPDOWN
// ════════════════════════════════════════════════════════════════
const yearSearchUrl = '{{ route("data.search_year") }}';
let yearTimeout  = null;
let yearAllCache = null;

function renderYearSuggestions(years) {
    const box         = document.getElementById('yearSuggestions');
    const currentYear = document.getElementById('yearSearch').value.trim();

    let html = `<button type="button" onclick="selectYear('')"
        class="w-full text-left px-4 py-2.5 flex items-center gap-2 border-b border-gray-100 hover:bg-gray-50">
        <i class="fas fa-layer-group text-gray-300 text-xs"></i>
        <span class="text-sm font-medium text-gray-700">Semua Tahun</span>
    </button>`;

    if (years.length === 0) {
        html += '<p class="px-4 py-3 text-xs text-gray-400 text-center">Tidak ada tahun tersedia</p>';
    } else {
        html += years.map(y => {
            const isSelected = String(y) === currentYear;
            return `<button type="button" onclick="selectYear(${y})"
                class="w-full text-left px-4 py-2.5 flex items-center gap-2 border-b border-gray-50
                    last:border-0 transition-colors
                    ${isSelected ? 'bg-amber-50 hover:bg-amber-100 text-amber-700 font-semibold' : 'hover:bg-gray-50 text-gray-700'}">
                <i class="fas fa-calendar-alt text-xs ${isSelected ? 'text-amber-400' : 'text-gray-300'}"></i>
                <span class="text-sm">${y}</span>
            </button>`;
        }).join('');
    }

    box.innerHTML = html;
    box.classList.remove('hidden');
}

function onYearFocus() {
    const box = document.getElementById('yearSuggestions');
    if (!box.classList.contains('hidden')) return;
    if (yearAllCache) { renderYearSuggestions(yearAllCache); return; }

    box.innerHTML = '<p class="px-4 py-3 text-xs text-gray-400 text-center"><i class="fas fa-circle-notch fa-spin mr-1"></i>Memuat...</p>';
    box.classList.remove('hidden');

    fetch(`${yearSearchUrl}?q=`)
        .then(r => r.json())
        .then(years => { yearAllCache = years; renderYearSuggestions(years); })
        .catch(() => { box.innerHTML = '<p class="px-4 py-3 text-xs text-red-400 text-center">Gagal memuat</p>'; });
}

function onYearInput() {
    clearTimeout(yearTimeout);
    const q = document.getElementById('yearSearch').value.trim();
    if (q.length === 0) {
        yearAllCache ? renderYearSuggestions(yearAllCache) : onYearFocus();
        return;
    }
    if (yearAllCache) {
        renderYearSuggestions(yearAllCache.filter(y => String(y).startsWith(q)));
        return;
    }
    yearTimeout = setTimeout(() => {
        fetch(`${yearSearchUrl}?q=${encodeURIComponent(q)}`)
            .then(r => r.json())
            .then(renderYearSuggestions)
            .catch(() => {});
    }, 200);
}

function selectYear(year) {
    document.getElementById('yearSearch').value = year || '';
    hideSuggestions('yearSuggestions');
    onFilterChange();
}

// ════════════════════════════════════════════════════════════════
// TUTUP DROPDOWN — klik di luar atau Escape
// ════════════════════════════════════════════════════════════════
document.addEventListener('click', function (e) {
    if (!document.getElementById('metadataDropdownWrap').contains(e.target)) {
        hideSuggestions('metadataSuggestions');
        const idVal  = document.getElementById('metadataId').value;
        const txtVal = document.getElementById('metadataSearch').value.trim();
        if (!idVal && txtVal) {
            document.getElementById('metadataSearch').value = '';
            document.getElementById('clearMetadata').classList.add('hidden');
            onFilterChange();
        }
    }
    if (!document.getElementById('wilayahDropdownWrap').contains(e.target)) {
        hideSuggestions('wilayahSuggestions');
        // Jika teks ada tapi ID kosong (user ketik tapi tidak pilih) → reset
        const idVal  = document.getElementById('wilayahId').value;
        const txtVal = document.getElementById('wilayahSearch').value.trim();
        if (!idVal && txtVal) {
            document.getElementById('wilayahSearch').value = '';
            document.getElementById('wilayahNama').value   = '';
            document.getElementById('clearWilayah').classList.add('hidden');
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
        hideSuggestions('wilayahSuggestions');
        hideSuggestions('yearSuggestions');
        closeTemplateModal();
    }
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
            id: id, metadata: row.dataset.metadata, metadataId: row.dataset.metadataId,
            lokasi: row.dataset.lokasi, waktu: row.dataset.waktu, nilai: row.dataset.nilai,
        };
        row.style.background = '#f5f3ff';
    } else {
        delete selectedRows[id];
        row.style.background = '';
    }
    updateSelectionUI();
}

function toggleAll(masterCb) {
    document.querySelectorAll('.row-check').forEach(cb => { cb.checked = masterCb.checked; onRowCheck(cb); });
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
    const bar   = document.getElementById('selectionBar');
    if (bar) bar.classList.toggle('hidden', count === 0);
    const selText = document.getElementById('selectionText');
    if (selText) selText.textContent = count + ' data dipilih';
}

function formatStatNumber(value) {
    if (value === null || value === undefined || value === '') return '-';
    const num = parseFloat(value);
    if (isNaN(num)) return value;
    return num.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
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
    document.getElementById('templateNama').value = '';
    document.getElementById('templateNamaError').classList.add('hidden');

    const f = getActiveFilter();

    const filterDefs = [
        { label: 'Metadata', value: f.metadataNama, color: '#eff6ff', text: '#1d4ed8' },
        { label: 'Wilayah',  value: f.wilayahNama,  color: '#f0fdf4', text: '#15803d' },
        { label: 'Tahun',    value: f.year,          color: '#fffbeb', text: '#b45309' },
    ].filter(fd => fd.value);

    const filterBox = document.getElementById('modalFilterBadges');
    filterBox.innerHTML = filterDefs.length === 0
        ? '<span class="text-xs text-gray-400 italic">Tidak ada filter aktif</span>'
        : filterDefs.map(fd =>
            `<span class="px-2.5 py-1 rounded-full text-xs font-medium border"
                style="background:${fd.color}; color:${fd.text}; border-color:${fd.text}33;">
                <span style="opacity:0.6; margin-right:4px;">${fd.label}:</span>${fd.value}
            </span>`).join('');

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
                <td class="px-3 py-2 font-medium text-gray-700">${escapeHtml(r.metadata || '-')}</td>
                <td class="px-3 py-2 text-gray-500 text-xs">${escapeHtml(r.lokasi || 'All')}</td>
                <td class="px-3 py-2 text-gray-500 text-xs">${escapeHtml(r.waktu || 'All')}</td>
                <td class="px-3 py-2 text-gray-700 font-semibold">${formatStatNumber(r.nilai)}</td>
                <td class="px-3 py-2 text-center">
                    <button onclick="removeFromModal('${r.id}')" class="text-red-400 hover:text-red-600">
                        <i class="fas fa-times-circle"></i>
                    </button>
                </td>
            </tr>`
        ).join('');
    }

    // Sync ke hidden form
    document.getElementById('formFilterMetadataId').value = f.metadataId;
    document.getElementById('formFilterWilayahId').value  = f.wilayahId;
    document.getElementById('formFilterYear').value       = f.year;

    document.getElementById('modalTemplate').classList.remove('hidden');
    setTimeout(() => document.getElementById('templateNama').focus(), 100);
}

function removeFromModal(id) {
    document.getElementById('modal-row-' + id)?.remove();
    delete selectedRows[id];
    const tableRow = document.getElementById('row-' + id);
    if (tableRow) { tableRow.querySelector('.row-check').checked = false; tableRow.style.background = ''; }
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
    document.getElementById('formDataIds').innerHTML =
        Object.keys(selectedRows).map(id => `<input type="hidden" name="data_ids[]" value="${id}">`).join('');
    document.getElementById('formSaveTemplate').submit();
}

document.getElementById('modalTemplate').addEventListener('click', function (e) {
    if (e.target === this) closeTemplateModal();
});

// Init
document.addEventListener('DOMContentLoaded', () => onFilterChange());
</script>
@endsection