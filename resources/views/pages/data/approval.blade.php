@extends('layouts.main')

@section('content')

    <a href="{{ route('data.index') }}"
        class="flex items-center gap-1 font-semibold text-sky-600 pt-6 ps-4 mb-4 hover:text-sky-900 text-sm transition-colors">
            <i class="fas fa-angle-left"></i> Kembali
    </a>

<div class="mt-2 bg-white rounded-xl shadow p-6">

    {{-- HEADER --}}
    <div class="flex justify-between items-start">
        <div>
            <h1 class="text-xl font-bold text-gray-800">Approval Data</h1>
            <p class="text-sm text-gray-400 mt-1">Verifikasi data sebelum ditampilkan ke halaman utama</p>
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

    {{-- STATS --}}
    <div class="grid grid-cols-3 gap-4 mt-6">
        <div class="bg-amber-50 border border-amber-100 rounded-lg p-4">
            <p class="text-xs text-amber-500 font-medium uppercase tracking-wide">Menunggu Verifikasi</p>
            <p class="text-2xl font-bold text-amber-700 mt-1">{{ number_format($pendingCount) }}</p>
            <p class="text-xs text-amber-400 mt-1">data pending</p>
        </div>
        <div class="bg-green-50 border border-green-100 rounded-lg p-4">
            <p class="text-xs text-green-500 font-medium uppercase tracking-wide">Sudah Disetujui</p>
            <p class="text-2xl font-bold text-green-700 mt-1">{{ number_format($approvedCount) }}</p>
            <p class="text-xs text-green-400 mt-1">data available</p>
        </div>
        <div class="bg-red-50 border border-red-100 rounded-lg p-4">
            <p class="text-xs text-red-500 font-medium uppercase tracking-wide">Ditolak</p>
            <p class="text-2xl font-bold text-red-700 mt-1">{{ number_format($rejectedCount) }}</p>
            <p class="text-xs text-red-400 mt-1">data rejected</p>
        </div>
    </div>

    {{-- FILTER --}}
    <form method="GET" class="mt-5 flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-48">
            <label class="block text-xs text-gray-500 font-medium mb-1">Filter Metadata</label>
            <select name="metadata_id"
                class="w-full border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-400 bg-white"
                onchange="this.form.submit()">
                <option value="">Semua Metadata</option>
                @foreach($metadataList as $meta)
                    <option value="{{ $meta->metadata_id }}"
                        {{ request('metadata_id') == $meta->metadata_id ? 'selected' : '' }}>
                        {{ $meta->nama }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="flex-1 min-w-36">
            <label class="block text-xs text-gray-500 font-medium mb-1">Filter Status</label>
            <select name="status"
                class="w-full border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-400 bg-white"
                onchange="this.form.submit()">
                <option value="0" {{ request('status', '0') == '0' ? 'selected' : '' }}>Pending</option>
                <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Approved</option>
                <option value="2" {{ request('status') == '2' ? 'selected' : '' }}>Ditolak</option>
            </select>
        </div>

        @if(request()->hasAny(['metadata_id']))
            <a href="{{ route('data.approval') }}"
               class="border border-gray-300 hover:bg-gray-50 text-gray-500 px-4 py-2 rounded-md text-sm transition-colors self-end">
                <i class="fas fa-times mr-1"></i> Reset
            </a>
        @endif

        {{-- Bulk approve button --}}
        @if($data->count() > 0 && request('status', '0') == '0')
            <div class="ml-auto self-end">
                <button type="button" onclick="confirmBulkApprove()"
                    class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md text-sm font-semibold
                           flex items-center gap-2 transition-colors shadow-md shadow-green-400/30">
                    <i class="fas fa-check-double"></i>
                    Setujui Semua di Halaman Ini
                </button>
            </div>
        @endif
    </form>

    {{-- TABLE --}}
    <div class="mt-4 border rounded-lg overflow-hidden">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider border-b">
                <tr>
                    <th class="px-4 py-3 font-semibold">No</th>
                    <th class="px-4 py-3 font-semibold">Metadata</th>
                    <th class="px-4 py-3 font-semibold">Lokasi</th>
                    <th class="px-4 py-3 font-semibold">Waktu</th>
                    <th class="px-4 py-3 font-semibold">Nilai</th>
                    <th class="px-4 py-3 font-semibold">Diinput Oleh</th>
                    <th class="px-4 py-3 font-semibold">Tanggal Input</th>
                    <th class="px-4 py-3 font-semibold">Status</th>
                    <th class="px-4 py-3 font-semibold text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($data as $index => $row)
                    <tr class="hover:bg-gray-50 transition-colors" id="row-{{ $row->id }}">
                        <td class="px-4 py-3 text-gray-400 text-xs">
                            {{ $data->firstItem() + $index }}
                        </td>
                        <td class="px-4 py-3">
                            <p class="font-semibold text-gray-800">{{ $row->metadata->nama ?? '-' }}</p>
                            @if($row->metadata?->klasifikasi)
                                <p class="text-xs text-gray-400">{{ $row->metadata->klasifikasi }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs">
                            @if($row->location)
                                <p class="font-medium text-gray-400">{{ $row->location->nama_wilayah }}</p>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs">
                            @if($row->time)

                                {{--  Jika hanya dekade   --}}
                                @if($row->time->month == 0 && $row->time->year == 0)
                                    <p class="font-medium text-gray-700">
                                        {{ $row->time->decade }}an
                                    </p>
                                    <p class="text-gray-400">Dekade</p>

                                {{-- jika hanya tahun --}}
                                @elseif($row->time->month == 0)

                                    <p class="font-medium text-gray-700">
                                        {{ $row->time->year }}
                                    </p>
                                    <p class="text-gray-400">Tahunan</p>
                                
                                {{-- Jika hanya Semester --}}
                                @elseif($row->time->day == 0 && $row->time->month == 0)
                                    <p class="font-medium text-gray-700">
                                        {{ $row->time->year }}, Semester {{ $row->time->semester }}
                                    </p>
                                    <p class="text-gray-400">Semester</p>
                                
                                {{-- Jika hanya Kuartal --}}
                                @elseif($row->time->day == 0 && $row->time->year == 0)
                                    <p class="font-medium text-gray-700">
                                        {{ $row->time->year }}, Kuartal {{ $row->time->quarter }}
                                    </p>
                                    <p class="text-gray-400">Kuartal</p>

                                {{-- jika sampai bulan --}}
                                @elseif($row->time->day == 0)

                                    <p class="font-medium text-gray-700">
                                        {{ \Carbon\Carbon::create($row->time->year, $row->time->month, 1)
                                            ->translatedFormat('F Y') }}
                                    </p>
                                    <p class="text-gray-400">Bulanan</p>

                                {{-- jika tanggal lengkap --}}
                                @else

                                    <p class="font-medium text-gray-700">
                                        {{ \Carbon\Carbon::create($row->time->year, $row->time->month, $row->time->day)
                                            ->translatedFormat('d F Y') }}
                                    </p>

                                @endif

                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if(!is_null($row->number_value))
                                <span class="font-semibold text-gray-800">
                                    {{ rtrim(rtrim(number_format($row->number_value, 2, ',', '.'), '0'), ',') }}
                                    <span class="text-xs font-normal text-gray-400">{{ $row->metadata?->satuan_data }}</span>
                                </span>
                            @else
                                <span class="text-gray-400 text-xs">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-500">
                            <p>{{ $row->user->name ?? '-' }}</p>
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-500">
                            {{ \Carbon\Carbon::parse($row->date_inputed)->translatedFormat('d M Y') }}
                            <p class="text-gray-400">
                                {{ \Carbon\Carbon::parse($row->date_inputed)->translatedFormat('H:i') }}
                            </p>
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $statusMap = [
                                    0 => ['label' => 'Pending',   'color' => 'yellow'],
                                    1 => ['label' => 'Available', 'color' => 'green'],
                                    2 => ['label' => 'Ditolak',   'color' => 'red'],
                                ];
                                $s = $statusMap[$row->status] ?? $statusMap[0];
                            @endphp
                            <span class="px-2 py-1 rounded-full text-xs font-semibold
                                bg-{{ $s['color'] }}-100 text-{{ $s['color'] }}-700">
                                {{ $s['label'] }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center gap-2 flex-wrap">
                                {{-- Detail --}}
                                <a href="{{ route('data.show', $row->id) }}"
                                   class="text-sky-500 hover:text-sky-700 text-xs font-medium transition-colors"
                                   title="Lihat Detail">
                                    <i class="fas fa-eye"></i>
                                </a>

                                @if($row->status == 0)
                                    {{-- Approve --}}
                                    <form action="{{ route('data.approve', $row->id) }}" method="POST"
                                          id="approve-form-{{ $row->id }}">
                                        @csrf
                                        <button type="button"
                                            onclick="confirmAction('approve', {{ $row->id }})"
                                            class="text-green-500 hover:text-green-700 text-xs font-medium transition-colors"
                                            title="Setujui">
                                            <i class="fas fa-check-circle"></i> Setujui
                                        </button>
                                    </form>

                                    {{-- Reject --}}
                                    <form action="{{ route('data.reject', $row->id) }}" method="POST"
                                          id="reject-form-{{ $row->id }}">
                                        @csrf
                                        <button type="button"
                                            onclick="confirmAction('reject', {{ $row->id }})"
                                            class="text-red-400 hover:text-red-600 text-xs font-medium transition-colors"
                                            title="Tolak">
                                            <i class="fas fa-times-circle"></i> Tolak
                                        </button>
                                    </form>
                                @elseif($row->status == 1)
                                    {{-- Bisa di-reject ulang --}}
                                    <form action="{{ route('data.reject', $row->id) }}" method="POST"
                                          id="reject-form-{{ $row->id }}">
                                        @csrf
                                        <button type="button"
                                            onclick="confirmAction('reject', {{ $row->id }})"
                                            class="text-red-400 hover:text-red-600 text-xs font-medium transition-colors">
                                            <i class="fas fa-ban"></i> Cabut
                                        </button>
                                    </form>
                                @elseif($row->status == 2)
                                    {{-- Bisa di-approve ulang --}}
                                    <form action="{{ route('data.approve', $row->id) }}" method="POST"
                                          id="approve-form-{{ $row->id }}">
                                        @csrf
                                        <button type="button"
                                            onclick="confirmAction('approve', {{ $row->id }})"
                                            class="text-green-500 hover:text-green-700 text-xs font-medium transition-colors">
                                            <i class="fas fa-redo"></i> Pulihkan
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-16 text-center">
                            <div class="flex flex-col items-center gap-3 text-gray-400">
                                <i class="fas fa-clipboard-check text-4xl text-gray-300"></i>
                                <p class="font-medium text-gray-500">
                                    @if(request('status', '0') == '0')
                                        Tidak ada data yang menunggu verifikasi
                                    @elseif(request('status') == '1')
                                        Belum ada data yang disetujui
                                    @else
                                        Belum ada data yang ditolak
                                    @endif
                                </p>
                                <a href="{{ route('data.index') }}"
                                   class="text-sky-500 hover:text-sky-700 text-sm font-medium transition-colors">
                                    <i class="fas fa-arrow-left mr-1"></i> Kembali ke Data
                                </a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- PAGINATION --}}
    @if($data->hasPages())
        <div class="mt-5 flex flex-col sm:flex-row items-center justify-between gap-3 text-sm text-gray-500">
            <p>
                Menampilkan {{ $data->firstItem() }}–{{ $data->lastItem() }}
                dari {{ number_format($data->total()) }} data
            </p>
            {{ $data->links() }}
        </div>
    @endif

</div>

{{-- MODAL KONFIRMASI --}}
<div id="confirmModal"
     class="fixed inset-0 bg-black/40 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl max-w-sm w-full p-6">
        <div class="flex items-center gap-3 mb-4">
            <div id="modalIcon"
                 class="w-10 h-10 rounded-full flex items-center justify-center shrink-0">
            </div>
            <div>
                <h3 id="modalTitle" class="font-bold text-gray-800"></h3>
                <p id="modalDesc" class="text-sm text-gray-500 mt-0.5"></p>
            </div>
        </div>
        <div class="flex justify-end gap-2 mt-4">
            <button onclick="closeModal()"
                class="border border-gray-300 text-gray-500 hover:bg-gray-50 px-4 py-2 rounded-md text-sm transition-colors">
                Batal
            </button>
            <button id="modalConfirmBtn" onclick="submitAction()"
                class="px-4 py-2 rounded-md text-sm font-semibold text-white transition-colors">
                Konfirmasi
            </button>
        </div>
    </div>
</div>

<script>
    // ── Live clock ──
    function updateDateTime() {
        const now = new Date();
        document.getElementById('current-date').textContent =
            now.toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
        document.getElementById('current-time').textContent =
            now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) + ' WITA';
    }
    updateDateTime();
    setInterval(updateDateTime, 1000);

    // ── Confirm modal ──
    let pendingAction = null;
    let pendingId     = null;

    function confirmAction(action, id) {
        pendingAction = action;
        pendingId     = id;

        const isApprove = action === 'approve';
        const icon      = document.getElementById('modalIcon');
        const btn       = document.getElementById('modalConfirmBtn');

        icon.className = 'w-10 h-10 rounded-full flex items-center justify-center shrink-0 ' +
            (isApprove ? 'bg-green-100' : 'bg-red-100');
        icon.innerHTML = isApprove
            ? '<i class="fas fa-check-circle text-green-600 text-lg"></i>'
            : '<i class="fas fa-times-circle text-red-500 text-lg"></i>';

        document.getElementById('modalTitle').textContent =
            isApprove ? 'Setujui Data?' : 'Tolak Data?';
        document.getElementById('modalDesc').textContent =
            isApprove
                ? 'Data akan ditandai sebagai Available dan akan muncul di halaman utama.'
                : 'Data akan ditolak dan tidak akan tampil di halaman utama.';

        btn.className = 'px-4 py-2 rounded-md text-sm font-semibold text-white transition-colors ' +
            (isApprove ? 'bg-green-500 hover:bg-green-600' : 'bg-red-500 hover:bg-red-600');
        btn.textContent = isApprove ? 'Ya, Setujui' : 'Ya, Tolak';

        document.getElementById('confirmModal').classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('confirmModal').classList.add('hidden');
        pendingAction = null;
        pendingId     = null;
    }

    function submitAction() {
        if (!pendingAction || !pendingId) return;
        const formId = pendingAction + '-form-' + pendingId;
        document.getElementById(formId).submit();
    }

    // ── Bulk approve ──
    function confirmBulkApprove() {
        if (!confirm('Setujui semua data di halaman ini?')) return;
        document.getElementById('bulkApproveForm').submit();
    }

    document.getElementById('confirmModal').addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });
</script>

{{-- Bulk approve form (hidden) --}}
<form id="bulkApproveForm" action="{{ route('data.bulk_approve') }}" method="POST" class="hidden">
    @csrf
    @foreach($data as $row)
        @if($row->status == 0)
            <input type="hidden" name="ids[]" value="{{ $row->id }}">
        @endif
    @endforeach
</form>

@endsection