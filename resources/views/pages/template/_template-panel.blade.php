{{-- ═══════════════════════════════════════════════════════════════
     _template-panel.blade.php
     Include dari pages/data/index.blade.php
═══════════════════════════════════════════════════════════════ --}}

@php
    $activeTemplateId = (int) request('template_id', 0);
    $activeTmpl       = $availableTemplates->firstWhere('tampilan_id', $activeTemplateId);
    $tahunOpts        = range(2010, 2050);
    $semesterOpts     = [1 => 'Semester 1', 2 => 'Semester 2'];
    $kuartalOpts      = [1 => 'Q1', 2 => 'Q2', 3 => 'Q3', 4 => 'Q4'];
    $bulanOpts        = [
        1=>'Januari', 2=>'Februari', 3=>'Maret', 4=>'April',
        5=>'Mei', 6=>'Juni', 7=>'Juli', 8=>'Agustus',
        9=>'September', 10=>'Oktober', 11=>'November', 12=>'Desember',
    ];
@endphp

<div class="mt-2 bg-white rounded-xl shadow p-6">

    {{-- ════ HEADER ════ --}}
    <div class="flex justify-between items-start mb-6">
        <div>
            <h2 class="text-lg font-bold text-gray-800">Tampilkan Data</h2>
            <p class="text-sm text-gray-400 mt-1">
                Pilih template → frekuensi → rentang periode → klik Tampilkan Data
            </p>
        </div>
        <a href="{{ route('template.create') }}"
           class="px-4 py-2 bg-sky-500 hover:bg-sky-600 text-white text-sm font-semibold rounded-lg
                  shadow-md shadow-sky-400/30 flex items-center gap-2 transition-colors">
            <i class="fas fa-plus"></i> Buat Template
        </a>
    </div>

    {{-- ════ ALERT ════ --}}
    @if(session('success'))
        <div class="mb-5 flex items-center gap-3 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
            <i class="fas fa-check-circle text-green-500 shrink-0"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- ════════════════════════════════════════
         STEP 1 — Pilih Template
    ════════════════════════════════════════ --}}
    <div class="mb-6">
        <div class="flex items-center gap-2 mb-3">
            <span class="w-5 h-5 rounded-full bg-violet-500 text-white text-xs font-bold
                          flex items-center justify-center shrink-0">1</span>
            <p class="text-sm font-semibold text-gray-700">Pilih Template</p>
        </div>

        @if($availableTemplates->isEmpty())
            <div class="flex flex-col w-full border border-gray-300 rounded-lg text-sm text-gray-500">
                <div class="border-b border-gray-300 px-3 py-2 text-xs font-medium text-gray-600">Daftar Template</div>
                <div class="flex flex-col items-center gap-3 py-12 text-gray-400">
                    <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center">
                        <i class="fas fa-layer-group text-gray-300 text-xl"></i>
                    </div>
                    <p class="font-medium text-gray-500">Belum ada template</p>
                    <p class="text-xs text-gray-400">Buat template pertama Anda untuk memudahkan akses data</p>
                    <a href="{{ route('template.create') }}"
                       class="mt-1 px-4 py-2 bg-violet-500 hover:bg-violet-600 text-white text-xs font-semibold rounded-lg transition-colors">
                        <i class="fas fa-plus mr-1"></i> Buat Template
                    </a>
                </div>
            </div>
        @else
            <div class="flex flex-col w-full border border-gray-300 rounded-lg">
                <div class="border-b border-gray-300 px-3 py-2 text-xs font-medium text-gray-600">
                    Daftar Template <span class="text-gray-400">({{ $availableTemplates->count() }})</span>
                </div>
                <div class="flex flex-col gap-2 my-3 mx-3 max-h-52 overflow-y-auto pr-1
                            scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-transparent">
                    @foreach($availableTemplates as $tmpl)
                        @php
                            $fp         = $tmpl->filter_params ?? [];
                            $jenis      = $fp['jenis_template'] ?? 'metadata';
                            $jenisLabel = ['metadata'=>'Metadata','klasifikasi'=>'Klasifikasi','wilayah'=>'Wilayah'][$jenis] ?? $jenis;
                            $isActive   = $activeTemplateId === (int) $tmpl->tampilan_id;
                        @endphp
                        <div class="group grid grid-cols-13 gap-5 w-full border-2 rounded-lg px-4 py-3
                                    text-xs font-semibold text-left items-center cursor-pointer transition-all duration-150
                                    {{ $isActive
                                        ? 'border-violet-500 bg-violet-500 text-white'
                                        : 'border-violet-300 text-violet-700 hover:bg-violet-600 hover:text-white' }}"
                             onclick="selectTemplate({{ $tmpl->tampilan_id }})">
                            <div class="col-span-6">
                                <p class="font-semibold">{{ $tmpl->nama_tampilan }}</p>
                                <div class="mt-1 flex items-center gap-2 font-normal opacity-80">
                                    <span>{{ $jenisLabel }}</span>
                                    <span>•</span>
                                    <span>{{ $tmpl->isi_tampilan_count ?? 0 }} metadata</span>
                                </div>
                            </div>
                            <div class="col-span-3 font-normal opacity-70">
                                <span class="block">Dibuat</span>
                                <span>{{ $tmpl->created_at?->format('Y-m-d H:i') }}</span>
                            </div>
                            <div class="col-span-3 font-normal opacity-70">
                                <span class="block">Diubah</span>
                                <span>{{ $tmpl->updated_at?->format('Y-m-d H:i') }}</span>
                            </div>
                            <div class="col-span-1 flex gap-2 justify-end" onclick="event.stopPropagation()">
                                <a href="{{ route('template.edit', $tmpl->tampilan_id) }}"
                                   class="{{ $isActive ? 'text-white/70 hover:text-white' : 'text-violet-400 hover:text-violet-600' }} transition-colors"
                                   title="Edit"><i class="fas fa-edit"></i></a>
                                <form action="{{ route('template.destroy', $tmpl->tampilan_id) }}" method="POST"
                                      onsubmit="return confirm('Hapus template \'{{ addslashes($tmpl->nama_tampilan) }}\'?')">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                            class="{{ $isActive ? 'text-white/70 hover:text-white' : 'text-violet-400 hover:text-red-500' }} transition-colors"
                                            title="Hapus"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- ════════════════════════════════════════
         STEP 2 — Pilih Frekuensi
    ════════════════════════════════════════ --}}
    <div id="stepFrekuensi" class="{{ $activeTemplateId ? '' : 'hidden' }} mb-6">
        <div class="flex items-center gap-2 mb-3">
            <span class="w-5 h-5 rounded-full bg-violet-500 text-white text-xs font-bold
                          flex items-center justify-center shrink-0">2</span>
            <p class="text-sm font-semibold text-gray-700">Pilih Frekuensi Rentang Waktu</p>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2">
            @foreach([
                '10tahunan'  => ['10 Tahunan',  'Dekade'],
                '5tahunan'   => ['5 Tahunan',   'Per 5 Tahun'],
                'tahunan'    => ['Tahunan',      'Per Tahun'],
                'semesteran' => ['Semesteran',   'S1 / S2'],
                'kuartal'    => ['Kuartal',      'Q1 – Q4'],
                'bulanan'    => ['Bulanan',       'Jan – Des'],
            ] as $key => [$label, $sub])
                <button type="button"
                        id="freq-btn-{{ $key }}"
                        onclick="selectFrekuensi('{{ $key }}')"
                        class="freq-btn border-2 border-gray-200 rounded-xl p-3 text-left
                               hover:border-violet-400 hover:bg-violet-50 transition-all duration-150">
                    <p class="text-xs font-semibold text-gray-700">{{ $label }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $sub }}</p>
                </button>
            @endforeach
        </div>
    </div>

    {{-- ════════════════════════════════════════
         STEP 3 — Rentang Periode (statis, tanpa AJAX)
    ════════════════════════════════════════ --}}
    <div id="stepPeriode" class="hidden mb-6">
        <div class="flex items-center gap-2 mb-3">
            <span class="w-5 h-5 rounded-full bg-violet-500 text-white text-xs font-bold
                          flex items-center justify-center shrink-0">3</span>
            <p class="text-sm font-semibold text-gray-700">Tentukan Rentang Periode</p>
        </div>

        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">

            {{-- ── Simple: 10tahunan / 5tahunan / tahunan ── --}}
            <div id="periodeSimple" class="hidden">
                <p class="text-xs text-gray-500 mb-3">
                    <i class="fas fa-info-circle mr-1 text-gray-400"></i>
                    Pilih rentang <span id="periodeSimpleLabel" class="font-semibold text-gray-700">tahun</span>
                </p>
                <div class="flex flex-wrap items-end gap-3">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Dari</label>
                        <select id="periodFromSimple" onchange="checkTampilkan()"
                                class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white
                                       focus:outline-none focus:ring-2 focus:ring-violet-400 min-w-28">
                            <option value="">Pilih...</option>
                            @foreach($tahunOpts as $y)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endforeach
                        </select>
                    </div>
                    <span class="text-gray-400 mb-2 text-sm">—</span>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Sampai</label>
                        <select id="periodToSimple" onchange="checkTampilkan()"
                                class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white
                                       focus:outline-none focus:ring-2 focus:ring-violet-400 min-w-28">
                            <option value="">Pilih...</option>
                            @foreach($tahunOpts as $y)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- ── Complex: semesteran / kuartal / bulanan ── --}}
            <div id="periodeComplex" class="hidden space-y-4">

                {{-- Tahun --}}
                <div>
                    <p class="text-xs text-gray-500 mb-2">
                        <i class="fas fa-info-circle mr-1 text-gray-400"></i>
                        Pilih rentang tahun terlebih dahulu
                    </p>
                    <div class="flex flex-wrap items-end gap-3">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Tahun Dari</label>
                            <select id="yearFrom" onchange="checkTampilkan()"
                                    class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white
                                           focus:outline-none focus:ring-2 focus:ring-violet-400 min-w-28">
                                <option value="">Pilih...</option>
                                @foreach($tahunOpts as $y)
                                    <option value="{{ $y }}">{{ $y }}</option>
                                @endforeach
                            </select>
                        </div>
                        <span class="text-gray-400 mb-2 text-sm">—</span>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Tahun Sampai</label>
                            <select id="yearTo" onchange="checkTampilkan()"
                                    class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white
                                           focus:outline-none focus:ring-2 focus:ring-violet-400 min-w-28">
                                <option value="">Pilih...</option>
                                @foreach($tahunOpts as $y)
                                    <option value="{{ $y }}">{{ $y }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Sub-periode (opsional) --}}
                <div>
                    <label class="block text-xs text-gray-500 mb-2">
                        <span id="periodeComplexLabel" class="font-semibold text-gray-700">Periode</span>
                        <span class="font-normal text-gray-400 ml-1">(opsional — kosongkan untuk semua)</span>
                    </label>
                    <div class="flex flex-wrap items-end gap-3">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Dari</label>
                            <select id="periodFromComplex" onchange="checkTampilkan()"
                                    class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white
                                           focus:outline-none focus:ring-2 focus:ring-violet-400 min-w-36">
                                <option value="">Semua</option>
                            </select>
                        </div>
                        <span class="text-gray-400 mb-2 text-sm">—</span>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Sampai</label>
                            <select id="periodToComplex" onchange="checkTampilkan()"
                                    class="border border-gray-300 rounded-md px-3 py-2 text-sm bg-white
                                           focus:outline-none focus:ring-2 focus:ring-violet-400 min-w-36">
                                <option value="">Semua</option>
                            </select>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- ════════════════════════════════════════
         STEP 4 — Tombol Tampilkan Data
    ════════════════════════════════════════ --}}
    <div id="stepTampilkan" class="hidden mb-6">
        <button type="button" onclick="tampilkanData()"
                class="px-6 py-2.5 bg-violet-600 hover:bg-violet-700 text-white text-sm font-semibold rounded-lg
                       shadow-md shadow-violet-400/30 flex items-center gap-2 transition-colors">
            <i class="fas fa-table"></i> Tampilkan Data
        </button>
    </div>

    {{-- ════════════════════════════════════════
         HASIL TABEL PIVOT
    ════════════════════════════════════════ --}}
    <div id="dataTableSection" class="hidden">
        <hr class="my-5 border-gray-100">

        <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
            <div>
                <p class="text-sm font-semibold text-gray-700" id="tableInfoText"></p>
                <p class="text-xs text-gray-400 mt-0.5" id="tableSubInfo"></p>
            </div>
            <div class="flex gap-2">
                <button type="button" onclick="exportCsv()"
                        class="px-3 py-1.5 border border-gray-300 hover:bg-gray-50 text-gray-600 text-xs
                               font-medium rounded-lg flex items-center gap-1.5 transition-colors">
                    <i class="fas fa-file-csv text-green-500"></i> Export CSV
                </button>
                <button type="button" onclick="resetFilter()"
                        class="px-3 py-1.5 border border-gray-300 hover:bg-gray-50 text-gray-500 text-xs
                               font-medium rounded-lg flex items-center gap-1.5 transition-colors">
                    <i class="fas fa-times text-xs"></i> Reset
                </button>
            </div>
        </div>

        {{-- Loading --}}
        <div id="tableLoading" class="hidden flex flex-col items-center gap-3 py-14 text-gray-400">
            <i class="fas fa-circle-notch fa-spin text-violet-400 text-3xl"></i>
            <p class="text-sm">Memuat data...</p>
        </div>

        {{-- Empty --}}
        <div id="tableEmpty"
             class="hidden flex flex-col items-center gap-3 py-14
                    border-2 border-dashed border-gray-200 rounded-xl text-gray-400">
            <i class="fas fa-inbox text-4xl text-gray-200"></i>
            <p class="text-sm font-medium text-gray-500" id="tableEmptyMsg">Tidak ada data ditemukan</p>
            <p class="text-xs">Coba ubah rentang periode</p>
        </div>

        {{-- Tabel --}}
        <div id="tableWrap" class="hidden overflow-x-auto rounded-xl border border-gray-200">
            <table class="w-full text-sm text-left border-collapse" id="pivotTable">
                <thead id="pivotHead" class="bg-gray-50 text-xs text-gray-600 uppercase tracking-wide"></thead>
                <tbody id="pivotBody" class="text-gray-700"></tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div id="tablePagination"
             class="hidden mt-4 flex items-center justify-between text-xs text-gray-500 flex-wrap gap-2">
            <span id="paginationInfo"></span>
            <div id="paginationBtns" class="flex gap-1 flex-wrap"></div>
        </div>
    </div>

</div>{{-- /card --}}

<script>
// ─── Constants ────────────────────────────────────────────────
const TMPL_URLS = {
    base      : '{{ route("data.index") }}',
    tableData : '{{ route("template.table_data") }}',
    csrf      : document.querySelector('meta[name="csrf-token"]')?.content ?? '',
};

// ─── State ────────────────────────────────────────────────────
const TS = {
    tampilan_id : {{ $activeTemplateId ?: 'null' }},
    frekuensi   : null,
    page        : 1,
};

// ─────────────────────────────────────────────────────────────
// STEP 1 — Pilih template
// ─────────────────────────────────────────────────────────────
function selectTemplate(id) {
    const url = (TS.tampilan_id === id)
        ? TMPL_URLS.base
        : TMPL_URLS.base + '?template_id=' + id;
    window.location.href = url;
}

// ─────────────────────────────────────────────────────────────
// STEP 2 — Pilih frekuensi → tampilkan dropdown statis langsung
// Tidak ada AJAX sama sekali di sini
// ─────────────────────────────────────────────────────────────
function selectFrekuensi(freq) {
    TS.frekuensi = freq;
    TS.page      = 1;

    // Reset tabel & tombol tampilkan
    _resetDataTable();
    document.getElementById('stepTampilkan').classList.add('hidden');

    // Highlight tombol frekuensi
    document.querySelectorAll('.freq-btn').forEach(btn => {
        btn.classList.remove('border-violet-500', 'bg-violet-50');
        btn.classList.add('border-gray-200');
    });
    const activeBtn = document.getElementById('freq-btn-' + freq);
    if (activeBtn) {
        activeBtn.classList.add('border-violet-500', 'bg-violet-50');
        activeBtn.classList.remove('border-gray-200');
    }

    // Tampilkan panel periode yang tepat
    const isSimple = ['10tahunan', '5tahunan', 'tahunan'].includes(freq);
    document.getElementById('periodeSimple').classList.toggle('hidden', !isSimple);
    document.getElementById('periodeComplex').classList.toggle('hidden', isSimple);
    document.getElementById('stepPeriode').classList.remove('hidden');

    if (isSimple) {
        // Reset nilai pilihan sebelumnya
        document.getElementById('periodFromSimple').value = '';
        document.getElementById('periodToSimple').value   = '';

        const labelMap = { '10tahunan': 'dekade', '5tahunan': 'periode 5 tahun', 'tahunan': 'tahun' };
        document.getElementById('periodeSimpleLabel').textContent = labelMap[freq] ?? freq;

    } else {
        // Reset nilai pilihan sebelumnya
        ['yearFrom','yearTo','periodFromComplex','periodToComplex']
            .forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });

        // Label sub-periode
        const labelMap = { 'semesteran': 'Semester', 'kuartal': 'Kuartal', 'bulanan': 'Bulan' };
        document.getElementById('periodeComplexLabel').textContent = labelMap[freq] ?? 'Periode';

        // Isi opsi sub-periode sesuai frekuensi
        _fillPeriodComplex(freq);
    }

    checkTampilkan();
}

// Isi dropdown sub-periode (semester / kuartal / bulan) — statis
function _fillPeriodComplex(freq) {
    let opts = [];
    if (freq === 'semesteran') {
        opts = [[1,'Semester 1'],[2,'Semester 2']];
    } else if (freq === 'kuartal') {
        opts = [[1,'Q1'],[2,'Q2'],[3,'Q3'],[4,'Q4']];
    } else if (freq === 'bulanan') {
        const bl = ['','Januari','Februari','Maret','April','Mei','Juni',
                    'Juli','Agustus','September','Oktober','November','Desember'];
        opts = Array.from({length:12}, (_,i) => [i+1, bl[i+1]]);
    }
    ['periodFromComplex','periodToComplex'].forEach(selId => {
        const el = document.getElementById(selId);
        if (!el) return;
        el.innerHTML = '<option value="">Semua</option>';
        opts.forEach(([v, l]) => {
            const o = document.createElement('option');
            o.value = v; o.textContent = l;
            el.appendChild(o);
        });
    });
}

// ─────────────────────────────────────────────────────────────
// Cek kondisi tombol "Tampilkan Data" boleh muncul atau tidak
// ─────────────────────────────────────────────────────────────
function checkTampilkan() {
    if (!TS.frekuensi) return;

    const isSimple = ['10tahunan', '5tahunan', 'tahunan'].includes(TS.frekuensi);
    let valid = false;

    if (isSimple) {
        // Wajib: Dari dan Sampai tahun terisi
        const from = document.getElementById('periodFromSimple')?.value;
        const to   = document.getElementById('periodToSimple')?.value;
        valid = !!from && !!to;
    } else {
        // Wajib: Tahun Dari dan Tahun Sampai terisi
        // Sub-periode boleh kosong (berarti ambil semua)
        const yf = document.getElementById('yearFrom')?.value;
        const yt = document.getElementById('yearTo')?.value;
        valid = !!yf && !!yt;
    }

    document.getElementById('stepTampilkan').classList.toggle('hidden', !valid);
}

// ─────────────────────────────────────────────────────────────
// STEP 4 — Fetch dan render tabel pivot
// ─────────────────────────────────────────────────────────────
async function tampilkanData(page = 1) {
    TS.page = page;

    const isSimple = ['10tahunan', '5tahunan', 'tahunan'].includes(TS.frekuensi);
    let payload;

    if (isSimple) {
        payload = {
            tampilan_id : TS.tampilan_id,
            frekuensi   : TS.frekuensi,
            period_from : parseInt(document.getElementById('periodFromSimple').value) || null,
            period_to   : parseInt(document.getElementById('periodToSimple').value)   || null,
            year_from   : null,
            year_to     : null,
            page,
        };
    } else {
        payload = {
            tampilan_id : TS.tampilan_id,
            frekuensi   : TS.frekuensi,
            year_from   : parseInt(document.getElementById('yearFrom').value)          || null,
            year_to     : parseInt(document.getElementById('yearTo').value)            || null,
            period_from : parseInt(document.getElementById('periodFromComplex').value) || null,
            period_to   : parseInt(document.getElementById('periodToComplex').value)   || null,
            page,
        };
    }

    // UI
    const section    = document.getElementById('dataTableSection');
    const loading    = document.getElementById('tableLoading');
    const empty      = document.getElementById('tableEmpty');
    const tableWrap  = document.getElementById('tableWrap');
    const pagination = document.getElementById('tablePagination');

    section.classList.remove('hidden');
    loading.classList.remove('hidden');
    empty.classList.add('hidden');
    tableWrap.classList.add('hidden');
    pagination.classList.add('hidden');
    section.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

    try {
        const res = await fetch(TMPL_URLS.tableData, {
            method  : 'POST',
            headers : {
                'Content-Type' : 'application/json',
                'X-CSRF-TOKEN' : TMPL_URLS.csrf,
                'Accept'       : 'application/json',
            },
            body: JSON.stringify(payload),
        });

        const d = await res.json();
        loading.classList.add('hidden');

        if (!d.success || !d.rows?.length) {
            empty.classList.remove('hidden');
            document.getElementById('tableEmptyMsg').textContent =
                d.message ?? 'Tidak ada data pada rentang periode yang dipilih.';
            return;
        }

        _renderTable(d);

    } catch (e) {
        loading.classList.add('hidden');
        empty.classList.remove('hidden');
        document.getElementById('tableEmptyMsg').textContent =
            'Terjadi kesalahan saat memuat data. Silakan coba lagi.';
        console.error(e);
    }
}

// ─────────────────────────────────────────────────────────────
// Render tabel pivot dengan header 2 baris + grouping indikator
// ─────────────────────────────────────────────────────────────
function _renderTable(d) {
    const head = document.getElementById('pivotHead');
    const body = document.getElementById('pivotBody');
    const cols = d.columns; // [{time_id, label}]
    const rows = d.rows;

    // ── Header 2 baris ───────────────────────────────────────
    const periodeHeader = {
        '10tahunan':'Dekade', '5tahunan':'Periode (5 Tahunan)',
        'tahunan':'Tahun', 'semesteran':'Semester',
        'kuartal':'Kuartal', 'bulanan':'Bulan',
    }[d.frekuensi] ?? 'Periode';

    head.innerHTML = `
        <tr class="border-b border-gray-200 bg-gray-50">
            <th class="px-4 py-3 font-semibold text-gray-600 sticky left-0 z-20 bg-gray-50
                        border-r border-gray-200 whitespace-nowrap min-w-52" rowspan="2">Nama</th>
            <th class="px-4 py-3 font-semibold text-gray-600 border-r border-gray-200
                        whitespace-nowrap" rowspan="2">Wilayah</th>
            <th class="px-4 py-3 font-semibold text-gray-600 text-center border-r border-gray-200"
                colspan="${cols.length}">${periodeHeader}</th>
            <th class="px-4 py-3 font-semibold text-gray-600 border-r border-gray-200
                        whitespace-nowrap text-center" rowspan="2">Satuan</th>
            <th class="px-4 py-3 font-semibold text-gray-600 whitespace-nowrap" rowspan="2">Sumber</th>
        </tr>
        <tr class="border-b border-gray-200 bg-gray-50">
            ${cols.map(c =>
                `<th class="px-4 py-2 text-center font-semibold text-gray-600
                             border-r border-gray-100 whitespace-nowrap">${_esc(c.label)}</th>`
            ).join('')}
        </tr>`;

    // ── Kelompokkan per metadata_id ──────────────────────────
    const grouped = new Map();
    rows.forEach(row => {
        if (!grouped.has(row.metadata_id)) {
            grouped.set(row.metadata_id, []);
        }
        grouped.get(row.metadata_id).push(row);
    });

    let html   = '';
    let rowIdx = 0;

    grouped.forEach(items => {
        const span = items.length;

        items.forEach((row, ri) => {
            const bg      = rowIdx % 2 === 0 ? 'bg-white' : 'bg-gray-50/40';
            const isFirst = ri === 0;
            rowIdx++;

            // Kolom nilai per periode
            const cells = cols.map(c => {
                const val = row.values?.[c.label];
                const fmt = (val !== null && val !== undefined && val !== '')
                    ? parseFloat(val).toLocaleString('id-ID', {
                        minimumFractionDigits: 0, maximumFractionDigits: 2
                      })
                    : `<span class="text-gray-300 select-none">—</span>`;
                return `<td class="px-4 py-2.5 text-right font-mono text-xs
                                    border-r border-gray-100 whitespace-nowrap">${fmt}</td>`;
            }).join('');

            html += `<tr class="${bg} hover:bg-violet-50/30 transition-colors">`;

            if (isFirst) {
                html += `
                    <td class="px-4 py-3 sticky left-0 z-10 ${bg} border-r border-gray-100 align-top"
                        rowspan="${span}">
                        <p class="font-semibold text-gray-800 text-xs leading-snug">${_esc(row.nama)}</p>
                        ${row.klasifikasi
                            ? `<p class="text-gray-400 text-xs mt-0.5">${_esc(row.klasifikasi)}</p>`
                            : ''}
                    </td>`;
            }

            html += `<td class="px-4 py-2.5 text-xs text-gray-600 border-r border-gray-100 whitespace-nowrap">
                        ${_esc(row.lokasi)}
                     </td>`;
            html += cells;

            if (isFirst) {
                html += `
                    <td class="px-4 py-2.5 text-xs text-gray-500 border-r border-gray-100
                                whitespace-nowrap text-center align-top" rowspan="${span}">
                        ${_esc(row.satuan ?? '—')}
                    </td>
                    <td class="px-4 py-2.5 text-xs text-gray-400 align-top max-w-48"
                        title="${_esc(row.sumber ?? '')}" rowspan="${span}">
                        <span class="line-clamp-3">${_esc(row.sumber ?? '—')}</span>
                    </td>`;
            }

            html += '</tr>';
        });

        // Garis pemisah antar indikator
        html += `<tr class="h-px"><td class="bg-gray-100 p-0" colspan="${3 + cols.length + 2}"></td></tr>`;
    });

    body.innerHTML = html;
    document.getElementById('tableWrap').classList.remove('hidden');

    // ── Info bar ─────────────────────────────────────────────
    const start = (d.current_page - 1) * d.per_page + 1;
    const end   = Math.min(d.current_page * d.per_page, d.total);
    const fLabel = {
        '10tahunan':'10 Tahunan','5tahunan':'5 Tahunan','tahunan':'Tahunan',
        'semesteran':'Semesteran','kuartal':'Kuartal','bulanan':'Bulanan',
    }[d.frekuensi] ?? d.frekuensi;

    document.getElementById('tableInfoText').textContent =
        `Menampilkan ${start}–${end} dari ${d.total} baris`;
    document.getElementById('tableSubInfo').textContent =
        `Frekuensi: ${fLabel}`;

    // ── Pagination ────────────────────────────────────────────
    if (d.last_page > 1) {
        document.getElementById('tablePagination').classList.remove('hidden');
        document.getElementById('paginationInfo').textContent =
            `Halaman ${d.current_page} dari ${d.last_page}`;

        let pages = [];
        for (let p = 1; p <= d.last_page; p++) {
            if (p === 1 || p === d.last_page || Math.abs(p - d.current_page) <= 2)
                pages.push(p);
            else if (pages.at(-1) !== '...')
                pages.push('...');
        }
        document.getElementById('paginationBtns').innerHTML = pages.map(p =>
            p === '...'
            ? `<span class="w-7 h-7 flex items-center justify-center text-gray-400 text-xs">…</span>`
            : `<button onclick="tampilkanData(${p})"
                       class="w-7 h-7 rounded-md text-xs font-medium transition-colors
                              ${p === d.current_page
                                ? 'bg-violet-500 text-white'
                                : 'border border-gray-300 text-gray-500 hover:bg-gray-50'}">
                   ${p}
               </button>`
        ).join('');
    }
}

// ─────────────────────────────────────────────────────────────
// Export CSV
// ─────────────────────────────────────────────────────────────
function exportCsv() {
    const table = document.getElementById('pivotTable');
    if (!table) return;
    const rows = [];
    table.querySelectorAll('tr').forEach(tr => {
        const cols = [];
        tr.querySelectorAll('th, td').forEach(td => {
            cols.push('"' + td.innerText.replace(/"/g,'""').replace(/\n/g,' ').trim() + '"');
        });
        if (cols.length) rows.push(cols.join(','));
    });
    const blob = new Blob(['\uFEFF' + rows.join('\n')], { type: 'text/csv;charset=utf-8;' });
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href = url;
    a.download = `data-template-${TS.tampilan_id ?? 'export'}.csv`;
    a.click();
    URL.revokeObjectURL(url);
}

// ─────────────────────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────────────────────
function resetFilter() { window.location.href = TMPL_URLS.base; }

function _resetDataTable() {
    ['dataTableSection','tableLoading','tableEmpty','tableWrap','tablePagination']
        .forEach(id => document.getElementById(id)?.classList.add('hidden'));
}

function _esc(str) {
    const d = document.createElement('div');
    d.innerText = str ?? '';
    return d.innerHTML;
}
</script>