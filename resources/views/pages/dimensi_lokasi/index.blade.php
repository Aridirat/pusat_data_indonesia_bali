@extends('layouts.main')

@section('content')
<div class="mt-2 bg-white rounded-md shadow p-6">

    <!-- HEADER -->
    <div class="flex justify-between items-start">
        <div>
            <h1 class="text-xl font-bold text-gray-800">Dimensi Lokasi</h1>
        </div>

        <div class="text-right text-sm text-gray-500">
            <p id="current-date">Loading date...</p>
            <p id="current-time">Loading time...</p>
        </div>
    </div>


    <!-- ACTION BAR -->
    <div class="flex justify-between items-center mt-6">

        <a href="{{ route('dimensi_lokasi.create') }}"
        class="bg-sky-600 hover:bg-sky-700 text-white px-4 py-2 rounded-md text-sm shadow">
            + Tambah Lokasi
        </a>

        <form method="GET" class="w-1/3">
            <input 
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Cari lokasi..."
                class="border rounded-md px-3 py-2 w-full text-sm focus:outline-none focus:ring-2 focus:ring-sky-400">
        </form>

    </div>


    <!-- TABLE -->
    <div class="mt-6 border rounded-md overflow-hidden">

        <table class="w-full text-xs">

            <thead class="bg-gray-200 text-gray-700">

                <tr>
                    <th class="p-3 text-left">
                        No.
                    </th>

                    <th class="p-3 text-left">
                        <a href="?sort_by=provinsi&sort_dir={{ $sortDir == 'asc' ? 'desc':'asc' }}">
                            Provinsi
                        </a>
                    </th>
                    <th class="p-3 text-left">
                        <a href="?sort_by=provinsi&sort_dir={{ $sortDir == 'asc' ? 'desc':'asc' }}">
                            Kode Prov.
                        </a>
                    </th>

                    <th class="p-3 text-left">
                        <a href="?sort_by=kabupaten&sort_dir={{ $sortDir == 'asc' ? 'desc':'asc' }}">
                            Kabupaten
                        </a>
                    </th>
                    <th class="p-3 text-left">
                        <a href="?sort_by=kabupaten&sort_dir={{ $sortDir == 'asc' ? 'desc':'asc' }}">
                            Kode Kab.
                        </a>
                    </th>

                    <th class="p-3 text-left">
                        <a href="?sort_by=kecamatan&sort_dir={{ $sortDir == 'asc' ? 'desc':'asc' }}">
                            Kecamatan
                        </a>
                    </th>

                    <th class="p-3 text-left">
                        <a href="?sort_by=kecamatan&sort_dir={{ $sortDir == 'asc' ? 'desc':'asc' }}">
                            Kode Kec.
                        </a>
                    </th>

                    <th class="p-3 text-left">
                        <a href="?sort_by=desa&sort_dir={{ $sortDir == 'asc' ? 'desc':'asc' }}">
                            Desa
                        </a>
                    </th>

                    <th class="p-3 text-left">
                        <a href="?sort_by=desa&sort_dir={{ $sortDir == 'asc' ? 'desc':'asc' }}">
                            Kode Desa
                        </a>
                    </th>

                    <th class="p-3 text-left">
                        Banjar
                    </th>
                    <th class="p-3 text-left">
                        RT
                    </th>

                </tr>

            </thead>


            <tbody class="divide-y">

                @forelse($data as $row)

                <tr class="hover:bg-gray-50">

                    <td class="p-3 bg-gray-50">
                        {{ ($data->currentPage() - 1) * $data->perPage() + $loop->iteration }}
                    </td>
                    
                    <td class="p-3">
                        {{ $row->provinsi }}
                    </td>

                    <td class="p-3 bg-gray-50">
                        {{ $row->kode_provinsi }}
                    </td>

                    <td class="p-3">
                        {{ $row->kabupaten }}
                    </td>

                    <td class="p-3 bg-gray-50">
                        {{ $row->kode_kabupaten }}
                    </td>

                    <td class="p-3">
                        {{ $row->kecamatan }}
                    </td>

                    <td class="p-3 bg-gray-50">
                        {{ $row->kode_kecamatan }}
                    </td>

                    <td class="p-3">
                        {{ $row->desa }}
                    </td>

                    <td class="p-3 bg-gray-50">
                        {{ $row->kode_desa }}
                    </td>

                    <td class="p-3">
                        {{ $row->banjar }}
                    </td>

                    <td class="p-3 bg-gray-50">
                        {{ $row->rt }}
                    </td>

                </tr>

                @empty

                <tr>
                    <td colspan="5" class="text-center p-5 text-gray-500">
                        Data lokasi tidak ditemukan
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
@endsection