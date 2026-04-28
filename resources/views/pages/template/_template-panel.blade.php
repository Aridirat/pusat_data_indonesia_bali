<div class="mt-2 bg-white rounded-xl shadow p-6">
    {{-- HEADER --}}
    <div class="flex justify-between items-start mb-7">
        <div>
            <h2 class="text-lg font-bold text-gray-800">Cari Data</h2>
            <p class="text-sm text-gray-400 mt-1">Temukan data yang Anda butuhkan dengan mengakses template Anda sebagai filter untuk menampilkan data, serta pilih frekuensi rentang waktu data yang ingin anda tampilkan.</p>
        </div>
    </div>

    {{-- ALERT --}}
    @if(session('success'))
        <div class="mt-4 flex items-center gap-3 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
            <i class="fas fa-check-circle text-green-500 shrink-0"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <div class="flex gap-2 my-6">
        <a href="{{ route('template.create') }}"
            class="px-4 py-2 bg-sky-500 hover:bg-sky-600 text-white text-sm font-semibold rounded-lg
                    shadow-md shadow-sky-400/30 flex items-center gap-2 transition-colors">
            <i class="fas fa-plus"></i> Buat Template Tampilan
        </a>
    </div>  

    {{-- Daftar Template --}}
    @if($availableTemplates->isEmpty())
        <div class="flex flex-col w-full border border-gray-300 rounded-lg text-sm text-gray-500">
            <div class="border-b border-gray-300 p-2">
                Daftar Template
            </div>

            <div class="flex flex-col items-center gap-3 py-12 text-gray-400">
                <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center">
                    <i class="fas fa-layer-group text-gray-300 text-xl"></i>
                </div>

                <p class="font-medium text-gray-500">
                    Belum ada template
                </p>

                <p class="text-xs text-gray-400">
                    Buat template pertama Anda untuk memudahkan akses data
                </p>

                <a href="{{ route('template.create') }}"
                class="mt-1 px-4 py-2 bg-violet-500 hover:bg-violet-600 text-white text-xs font-semibold rounded-lg transition-colors">
                    <i class="fas fa-plus mr-1"></i> Buat Template
                </a>
            </div>
        </div>
    @else
        <div class="flex flex-col w-full border border-gray-300 rounded-lg text-sm text-gray-500">
            {{-- Header --}}
            <div class="border-b border-gray-300 p-2">
                Daftar Template
            </div>

            {{-- List --}}
            <div 
                class="flex flex-col gap-2 my-4 mx-3 
                    max-h-50 overflow-y-auto pr-1
                    scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-transparent"
                id="templateList"
            >
                @foreach($availableTemplates as $tmpl)
                    @php
                        $fp = $tmpl->filter_params ?? [];
                        $jenis = $fp['jenis_template'] ?? 'metadata';
                        $jenisLabel = [
                            'metadata' => 'Metadata',
                            'klasifikasi' => 'Klasifikasi',
                            'wilayah' => 'Wilayah'
                        ][$jenis] ?? $jenis;

                        $isActive = (string)(request('template_id')) === (string)$tmpl->tampilan_id;
                    @endphp

                    <div
                        class="template-card group grid grid-cols-13 gap-5 w-full border-2 rounded-lg px-4 py-3 text-xs font-semibold text-left items-center justify-between cursor-pointer transition-all duration-150
                        {{ $isActive
                            ? 'border-violet-500 bg-violet-500 text-white'
                            : 'border-violet-300 text-violet-700 hover:bg-violet-600 hover:text-white'
                        }}"
                        id="tcard-{{ $tmpl->tampilan_id }}"
                        onclick="selectTemplate({{ $tmpl->tampilan_id }})"
                    >
                        {{-- Nama Template --}}
                        <div class="col-span-6">
                            <p class="font-semibold">
                                {{ $tmpl->nama_tampilan }}
                            </p>

                            <div class="mt-1 flex items-center gap-2 text-xs font-normal">
                                <span>
                                    {{ $jenisLabel }}
                                </span>

                                <span>
                                    •
                                </span>

                                <span>
                                    {{ $tmpl->isi_tampilan_count ?? 0 }} metadata
                                </span>
                            </div>
                        </div>

                        {{-- Created At --}}
                        <div class="col-span-3">
                            <p>
                                <span class="text-xs font-normal">
                                    Created at
                                    {{ $tmpl->created_at?->format('Y-m-d H:i:s') }}
                                </span>
                            </p>
                        </div>

                        {{-- Updated At --}}
                        <div class="col-span-3">
                            <p>
                                <span class="text-xs font-normal">
                                    Edited at
                                    {{ $tmpl->updated_at?->format('Y-m-d H:i:s') }}
                                </span>
                            </p>
                        </div>

                        {{-- Action --}}
                        <div
                            class="col-span-1 flex flex-row gap-3 p-1 justify-end"
                            onclick="event.stopPropagation()"
                        >
                            {{-- Edit --}}
                            <a href="{{ route('template.edit', $tmpl->tampilan_id) }}">
                                <i class="fas fa-pen"></i>
                            </a>

                            {{-- Delete --}}
                            <form
                                action="{{ route('template.destroy', $tmpl->tampilan_id) }}"
                                method="POST"
                                onsubmit="return confirm('Hapus template \'{{ addslashes($tmpl->nama_tampilan) }}\'?')"
                            >
                                @csrf
                                @method('DELETE')

                                <button
                                    type="submit"
                                    
                                >
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <form method="GET" id="filterForm" class="mt-4 space-y-3">
        @if(request('template_id'))
            <input type="hidden" name="template_id" value="{{ request('template_id') }}">
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            {{-- Frekuensi --}}
            <div>
                <label for="frekuensi" class="block text-xs text-gray-500 font-medium mb-1">
                    <i class="fas fa-clock mr-1 text-gray-400"></i> Frekuensi Rentang Waktu
                </label>
                <select name="frekuensi" id="frekuensi" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-400 bg-white">
                    <option value="">Rentang Waktu Menampilkan Data</option>
                    <option value="dekade">Dekade (dalam rentang 10 Tahun (misal periode 2020-2029))</option>
                    <option value="tahunan">Tahunan (dalam rentang Tahun berapapun (misal periode 2020-2030))</option>
                    <option value="semester">Semester (dalam rentang semester pada suatu tahun (periode s1-s2))</option>
                    <option value="kuartal">Kuartal (dalam rentang kuartal pada suatu tahun(periode q1-q4))</option>
                    <option value="bulanan">Bulanan (dalam rentang bulan (periode 1-12))</option>
                </select>
            </div>
        </div>
        <div class="flex flex-col gap-3 my-5">
            <li class="text-xs text-gray-600">Anda wajib mengisi tahun apabila rentang waktu yang dipilih adalah semester, kuarta, atau bulanan.</li>
            <li class="text-xs text-gray-600">Anda hanya mengisi rentang periode apabila rentang waktu yang dipilih adalah tahunan atau dekade.</li>
            <div class="col-1">
                <div class="flex gap-4 items-center">
                    <label for="frekuensi" class="block text-xs text-gray-500 font-medium mb-1">
                        Tahun
                    </label>
                    <select name="frekuensi" id="frekuensi" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-400 bg-white">
                        <option value="">Pilih Tahun</option>
                    </select>

                    <label for="frekuensi" class="block text-xs text-gray-500 font-medium mb-1">
                        Periode
                    </label>
                    <select name="frekuensi" id="frekuensi" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-400 bg-white">
                        <option value="">Dari</option>
                    </select>
                    <label for="periode2" class="text-xs text-gray-500 font-medium mb-1">
                        -
                    </label>
                    <select name="periode2" id="periode2"  class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-400 bg-white">
                        <option value="">Sampai</option>
                    </select>
                </div>
            </div>
        </div>
        @if ($activeMetadataNama == "dekade" || $activeMetadataNama == "tahunan")
        @elseif($activeMetadataNama == "semester" || $activeMetadataNama == "kuartal" || $activeMetadataNama == "bulanan")
            <div class="flex flex-col gap-3 my-5">
                <div class="col-1">
                    <div class="flex gap-4 items-center">
                        <label for="frekuensi" class="block text-xs text-gray-500 font-medium mb-1">
                            Periode
                        </label>
                        <select name="frekuensi" id="frekuensi" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-400 bg-white">
                            <option value="">Dari</option>
                        </select>
                        <label for="periode2" class="text-xs text-gray-500 font-medium mb-1">
                            -
                        </label>
                        <select name="periode2" id="periode2"  class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-400 bg-white">
                            <option value="">Sampai</option>
                        </select>
                    </div>
                </div>
            </div>
        @endif

        {{-- Tombol aksi filter --}}
        <div class="flex gap-2 items-center flex-wrap pt-1">
            <button type="submit" id="btnApplyFilter"
                class="{{ $hasFilter ? '' : 'hidden' }} bg-sky-500 hover:bg-sky-600 text-white px-5 py-2
                       rounded-md text-sm font-semibold transition-colors flex items-center gap-2">
                <i class="fas fa-filter"></i> Terapkan Filter
            </button>

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
                    @if($activeWilayah)
                        <span class="bg-emerald-50 text-emerald-600 border border-emerald-200 text-xs px-2 py-0.5 rounded-full">
                            <i class="fas fa-map-marker-alt mr-1 text-emerald-300 text-xs"></i>{{ $activeWilayah }}
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

    <hr class="my-4">

<div class="mt-2 bg-white rounded-xl shadow p-6">

    {{-- ═══ PANEL DATA TEMPLATE (muncul ketika template dipilih) ═══ --}}
    @if(request('template_id'))
        <div id="templateDataPanel">
            @php
                $activeTmpl = $availableTemplates->firstWhere('tampilan_id', (int)request('template_id'));
            @endphp

            @if($activeTmpl)
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-gray-800 text-base flex items-center gap-2">
                            <i class="fas fa-layer-group text-violet-500"></i>
                            {{ $activeTmpl->nama_tampilan }}
                        </h3>
                        <p class="text-xs text-gray-400 mt-0.5">Pilih frekuensi dan rentang waktu untuk menampilkan data</p>
                    </div>
                    <a href="{{ route('data.index') }}"
                       class="text-xs text-gray-400 hover:text-gray-600 flex items-center gap-1 transition-colors">
                        <i class="fas fa-times text-xs"></i> Tutup
                    </a>
                </div>

                {{-- TAB FREKUENSI --}}
                <div class="border-b border-gray-200 mb-5">
                    <div class="flex gap-1 overflow-x-auto" id="panelFreqTabs">
                        @foreach(['dekade'=>'Dekade','tahunan'=>'Tahunan','semester'=>'Semester','kuartal'=>'Kuartal','bulanan'=>'Bulanan'] as $fkey => $flabel)
                            <button type="button"
                                id="ptab-{{ $fkey }}"
                                onclick="switchPanelTab('{{ $fkey }}')"
                                class="ptab-btn shrink-0 px-4 py-2.5 text-xs font-semibold border-b-2 border-transparent
                                       text-gray-400 cursor-not-allowed transition-colors"
                                disabled>
                                {{ $flabel }}
                                <span id="ptab-count-{{ $fkey }}"
                                      class="ml-1 text-xs font-bold px-1.5 py-0.5 rounded-full bg-gray-100 text-gray-400">0</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- TABEL DATA TEMPLATE --}}
                <div id="panelTableWrap">
                    <div class="flex flex-col items-center gap-3 py-12 text-gray-400">
                        <i class="fas fa-hand-pointer text-4xl text-gray-200"></i>
                        <p class="text-sm text-gray-500">Pilih tab frekuensi di atas untuk memuat data</p>
                    </div>
                </div>

            @endif
        </div>
    @endif
</div>

<script>
// ─────────────────────────────────────────────────────────────
// TEMPLATE SELECTION (klik card → redirect dengan template_id)
// ─────────────────────────────────────────────────────────────
function selectTemplate(id) {
    const current = '{{ request("template_id") }}';
    if (String(current) === String(id)) {
        window.location.href = '{{ route("data.index") }}';
    } else {
        window.location.href = '{{ route("data.index") }}' + '?template_id=' + id;
    }
}

// ─────────────────────────────────────────────────────────────
// PANEL DATA — hanya aktif jika template dipilih
// ─────────────────────────────────────────────────────────────
@if(request('template_id') && isset($activeTmpl))
const TMPL_SHOW_URL   = '{{ route("template.show", ["tampilan" => request("template_id")]) }}';
const FETCH_DATA_URL  = '{{ route("template.fetch_data") }}';
let panelGrouped      = null;
let panelActiveTab    = '';
let panelCurrentPage  = 1;
const PANEL_PAGE_SIZE = 10;

// Auto-load template metadata saat halaman tampil
document.addEventListener('DOMContentLoaded', async () => {
    try {
        const r = await fetch(TMPL_SHOW_URL);
        const d = await r.json();
        if (!d.success) return;
        panelGrouped = d.grouped;
        // Aktifkan tab yang punya data
        let firstActive = '';
        Object.entries(panelGrouped).forEach(([freq, items]) => {
            const count = items.length;
            const btn   = document.getElementById('ptab-' + freq);
            const cnt   = document.getElementById('ptab-count-' + freq);
            if (btn && cnt) {
                cnt.textContent = count;
                if (count > 0) {
                    btn.disabled = false;
                    btn.classList.remove('cursor-not-allowed','text-gray-400');
                    btn.classList.add('cursor-pointer','text-gray-600','hover:text-gray-800');
                    cnt.classList.remove('bg-gray-100','text-gray-400');
                    cnt.classList.add('bg-violet-100','text-violet-600');
                    if (!firstActive) firstActive = freq;
                }
            }
        });
        if (firstActive) switchPanelTab(firstActive);
    } catch(e) { console.error('Gagal load template data:', e); }
});

function switchPanelTab(freq) {
    panelActiveTab = freq;
    panelCurrentPage = 1;

    Object.keys({dekade:1,tahunan:1,semester:1,kuartal:1,bulanan:1}).forEach(f => {
        const btn = document.getElementById('ptab-' + f);
        if (!btn) return;
        if (f === freq) { btn.classList.add('border-violet-500','text-violet-600'); btn.classList.remove('border-transparent','text-gray-600'); }
        else { btn.classList.remove('border-violet-500','text-violet-600'); if (!btn.disabled) btn.classList.add('border-transparent','text-gray-600'); }
    });

    const pf = document.getElementById('panelPeriodeFilter');
    if (pf) {
        pf.classList.remove('hidden');
        if (['dekade','tahunan'].includes(freq)) {
            document.getElementById('panelPeriodeSimple').classList.remove('hidden');
            document.getElementById('panelPeriodeComplex').classList.add('hidden');
        } else {
            document.getElementById('panelPeriodeSimple').classList.add('hidden');
            document.getElementById('panelPeriodeComplex').classList.remove('hidden');
            const lbl = document.getElementById('panelPeriodeLabel');
            if (lbl) lbl.textContent = freq === 'semester' ? 'Semester:' : (freq === 'kuartal' ? 'Kuartal:' : 'Bulan:');
        }
    }

    renderPanelTable(freq);
}

function renderPanelTable(freq) {
    if (!panelGrouped) return;
    const items = panelGrouped[freq] || [];
    const wrap  = document.getElementById('panelTableWrap');
    if (!wrap) return;

    if (!items.length) {
        wrap.innerHTML = `<div class="flex flex-col items-center gap-2 py-10 text-gray-400">
            <i class="fas fa-inbox text-3xl text-gray-200"></i>
            <p class="text-sm text-gray-500">Tidak ada metadata dengan frekuensi ini dalam template</p>
        </div>`;
        return;
    }

    const start = (panelCurrentPage - 1) * PANEL_PAGE_SIZE;
    const end   = start + PANEL_PAGE_SIZE;
    const paged = items.slice(start, end);

    let rows = paged.map(m => {
        const locs = m.locations && m.locations.length
            ? m.locations
            : [{ location_id: 0, nama_wilayah: 'Semua Wilayah', has_children: false }];

        return locs.map((loc, li) => `
            <tr class="hover:bg-violet-50 transition-colors">
                ${li === 0 ? `<td class="px-4 py-3 align-top" rowspan="${locs.length}">
                    <p class="font-semibold text-gray-800 text-xs">${escHtml(m.nama)}</p>
                    <p class="text-xs text-gray-400">${escHtml(m.satuan_data||'')} · <em>${escHtml(m.frekuensi_penerbitan||'')}</em></p>
                </td>
                <td class="px-4 py-3 align-top text-xs text-gray-500" rowspan="${locs.length}">${escHtml(m.klasifikasi||'-')}</td>` : ''}
                <td class="px-4 py-3 text-xs">
                    <div class="flex items-center gap-2">
                        <span class="text-gray-600">${escHtml(loc.nama_wilayah)}</span>
                        ${loc.has_children ? `<button type="button"
                            class="text-sky-400 hover:text-sky-600 text-xs" title="Lihat turunan">
                            <i class="fas fa-chevron-down"></i>
                        </button>` : ''}
                    </div>
                </td>
            </tr>`
        ).join('');
    }).join('');

    const totalPages = Math.ceil(items.length / PANEL_PAGE_SIZE);

    wrap.innerHTML = `
        <div class="border rounded-xl overflow-hidden">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider border-b">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Metadata</th>
                        <th class="px-4 py-3 font-semibold">Klasifikasi</th>
                        <th class="px-4 py-3 font-semibold">Wilayah</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">${rows}</tbody>
            </table>
        </div>
        ${totalPages > 1 ? `
        <div class="mt-3 flex items-center justify-between text-xs text-gray-500">
            <span>Menampilkan ${start+1}–${Math.min(end,items.length)} dari ${items.length}</span>
            <div class="flex gap-1">
                ${Array.from({length:totalPages}, (_,i) => i+1).map(p =>
                    `<button onclick="panelGoPage(${p})" class="w-7 h-7 rounded-md font-medium text-xs ${p===panelCurrentPage?'bg-violet-500 text-white':'border border-gray-200 text-gray-500 hover:bg-gray-50'}">${p}</button>`
                ).join('')}
            </div>
        </div>` : ''}
    `;
}

function panelGoPage(p) { panelCurrentPage = p; renderPanelTable(panelActiveTab); }

function loadPanelData() { renderPanelTable(panelActiveTab); }

function resetPanelFilter() {
    ['panelPeriodFromSimple','panelPeriodToSimple','panelYearFrom','panelYearTo','panelPeriodFrom','panelPeriodTo']
        .forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
    renderPanelTable(panelActiveTab);
}

function escHtml(str) { const d = document.createElement('div'); d.innerText = str||''; return d.innerHTML; }
@endif
</script>