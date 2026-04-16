@extends('layouts.main')

@section('content')
<div class="mt-2 bg-white rounded-md shadow p-6">

    {{-- HEADER --}}
    <div class="flex justify-between items-start">
        <div>
            <h1 class="text-xl font-bold text-gray-800">Dimensi Waktu</h1>
            <p class="text-sm text-gray-400 mt-1">Kelola data dimensi waktu</p>
        </div>
        <div class="text-right text-sm text-gray-500">
            <p id="current-date">Loading date...</p>
            <p id="current-time" class="font-mono text-sky-600 font-semibold"></p>
        </div>
    </div>

    {{-- SUCCESS / ERROR ALERT --}}
    @if(session('success'))
        <div class="mt-4 flex items-center gap-3 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
            <i class="fas fa-check-circle text-green-500"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="mt-4 flex items-center gap-3 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
            <i class="fas fa-exclamation-circle text-red-500"></i>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    {{-- ACTION BAR --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mt-6 gap-3">
        <a href="{{ route('dimensi_waktu.create') }}"
           class="bg-sky-600 hover:bg-sky-700 text-white px-4 py-2 rounded-md text-sm shadow flex items-center gap-2 transition-colors">
            <i class="fas fa-plus"></i>
            Tambah Waktu
        </a>

        <form method="GET" class="flex gap-2 w-full sm:w-auto">
            @csrf

            {{-- Filter Tahun --}}
            <select name="year"
                class="border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-400 bg-white text-gray-600"
                onchange="this.form.submit()">
                <option value="">Semua Tahun</option>
                @foreach($availableYears as $year)
                    <option value="{{ $year }}" {{ request('year') == $year ? 'selected' : '' }}>
                        {{ $year }}
                    </option>
                @endforeach
            </select>

            {{-- Search --}}
            <div class="relative">
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Cari tahun..."
                    class="border rounded-md pl-8 pr-3 py-2 w-48 text-sm focus:outline-none focus:ring-2 focus:ring-sky-400">
                <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
            </div>

            @if(request('search') || request('year'))
                <a href="{{ route('dimensi_waktu.index') }}"
                   class="border rounded-md px-3 py-2 text-sm text-gray-500 hover:bg-gray-50 transition-colors flex items-center gap-1">
                    <i class="fas fa-times text-xs"></i> Reset
                </a>
            @endif
        </form>
    </div>

    {{-- TABLE --}}
    <div class="mt-4 border rounded-lg overflow-hidden">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider border-b">
                <tr>
                    <th class="px-4 py-3 font-semibold">No</th>
                    <th class="px-4 py-3 font-semibold">Dekade</th>
                    <th class="px-4 py-3 font-semibold">Tahun</th>
                    <th class="px-4 py-3 font-semibold">Periode</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($data as $index => $row)
                    <tr class="hover:bg-sky-50 transition-colors">
                        <td class="px-4 py-3 text-gray-400">
                            {{ $data->firstItem() + $index }}
                        </td>
                        <td class="px-4 py-3 text-gray-700">
                            {{ $row->decade }}an
                        </td>
                        <td class="px-4 py-3 font-semibold text-gray-800">
                            @if ($row->year == 0)
                                <span class="text-gray-500">ALL</span>
                            @else
                                {{ $row->year }}
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if ($row->semester == 0 && $row->quarter == 0 && $row->month == 0)
                                <span class="text-gray-500">ALL</span>

                            @elseif ($row->month != 0)
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">
                                    {{ \Carbon\Carbon::create($row->year ?: 2000, $row->month, 1)->translatedFormat('F') }}
                                </span>

                            @elseif ($row->quarter != 0)
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium
                                    {{ $row->quarter == 1 ? 'bg-sky-100 text-sky-700' : '' }}
                                    {{ $row->quarter == 2 ? 'bg-emerald-100 text-emerald-700' : '' }}
                                    {{ $row->quarter == 3 ? 'bg-amber-100 text-amber-700' : '' }}
                                    {{ $row->quarter == 4 ? 'bg-rose-100 text-rose-700' : '' }}
                                ">
                                    Kuartal {{ $row->quarter }}
                                </span>

                            @elseif ($row->semester != 0)
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium
                                    {{ $row->semester == 1 ? 'bg-cyan-100 text-cyan-700' : '' }}
                                    {{ $row->semester == 2 ? 'bg-green-100 text-green-700' : '' }}
                                ">
                                    Semester {{ $row->semester }}
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-16 text-center">
                            <div class="flex flex-col items-center gap-3 text-gray-400">
                                <i class="fas fa-calendar-times text-4xl text-gray-300"></i>
                                <p class="font-medium text-gray-500">Belum ada data dimensi waktu</p>
                                <p class="text-sm">Klik tombol <strong>Tambah Tahun</strong> untuk mulai menambahkan data.</p>
                                <a href="{{ route('dimensi_waktu.create') }}"
                                   class="mt-2 bg-sky-600 hover:bg-sky-700 text-white px-4 py-2 rounded-md text-sm transition-colors">
                                    + Tambah Tahun
                                </a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

  
        <!-- PAGINATION -->
    @if(isset($data))
    <div class="mt-5">
        {{ $data->links() }}
    </div>
    @endif

</div>

<script>
    // Live clock & date
    function updateDateTime() {
        const now = new Date();
        const dateOptions = { weekday: 'long', year: 'numeric', month: 'numeric' };
        const timeOptions = { hour: '2-digit', minute: '2-digit', second: '2-digit' };
        document.getElementById('current-date').textContent =
            now.toLocaleDateString('id-ID', dateOptions);
        document.getElementById('current-time').textContent =
            now.toLocaleTimeString('id-ID', timeOptions);
    }
    updateDateTime();
    setInterval(updateDateTime, 1000);
</script>
@endsection