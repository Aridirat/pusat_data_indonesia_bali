@extends('layouts.main')

@section('content')
<div class="mt-2 bg-white rounded-xl shadow p-6">
    {{-- BREADCRUMB --}}
    <div class="flex items-center gap-2 text-sm text-gray-400 mb-5">
        <a href="{{ route('data.index') }}" class="hover:text-sky-500 transition-colors">Data</a>
        <i class="fas fa-chevron-right text-xs"></i>
        <span class="text-gray-600 font-medium">Buat Template Tampilan</span>
    </div>

    <div class="mb-6">
        <h1 class="text-xl font-bold text-gray-800">Buat Template Tampilan Baru</h1>
        <p class="text-sm text-gray-400 mt-1">Pilih jenis template sesuai kebutuhan Anda</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mt-6">
        {{-- WILAYAH --}}
        <a href="{{ route('template.create.wilayah') }}"
           class="group flex flex-col gap-4 border-2 border-gray-100 hover:border-emerald-400 rounded-2xl p-6
                  transition-all duration-200 hover:shadow-lg hover:shadow-emerald-100 cursor-pointer">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 group-hover:bg-emerald-100 flex items-center justify-center transition-colors">
                <i class="fas fa-map-marker-alt text-emerald-500 text-lg"></i>
            </div>
            <div>
                <h3 class="font-bold text-gray-800 text-base">Template Wilayah</h3>
            </div>
            <div class="mt-auto flex items-center gap-1.5 text-emerald-500 text-sm font-semibold group-hover:gap-2.5 transition-all">
                Pilih Jenis Ini <i class="fas fa-arrow-right text-xs"></i>
            </div>
        </a>

        {{-- KLASIFIKASI --}}
        <a href="{{ route('template.create.klasifikasi') }}"
           class="group flex flex-col gap-4 border-2 border-gray-100 hover:border-violet-400 rounded-2xl p-6
                  transition-all duration-200 hover:shadow-lg hover:shadow-violet-100 cursor-pointer">
            <div class="w-12 h-12 rounded-xl bg-violet-50 group-hover:bg-violet-100 flex items-center justify-center transition-colors">
                <i class="fas fa-tags text-violet-500 text-lg"></i>
            </div>
            <div>
                <h3 class="font-bold text-gray-800 text-base">Template Klasifikasi</h3>
            </div>
            <div class="mt-auto flex items-center gap-1.5 text-violet-500 text-sm font-semibold group-hover:gap-2.5 transition-all">
                Pilih Jenis Ini <i class="fas fa-arrow-right text-xs"></i>
            </div>
        </a>

        {{-- METADATA --}}
        <a href="{{ route('template.create.metadata') }}"
           class="group flex flex-col gap-4 border-2 border-gray-100 hover:border-sky-400 rounded-2xl p-6
                  transition-all duration-200 hover:shadow-lg hover:shadow-sky-100 cursor-pointer">
            <div class="w-12 h-12 rounded-xl bg-sky-50 group-hover:bg-sky-100 flex items-center justify-center transition-colors">
                <i class="fas fa-database text-sky-500 text-lg"></i>
            </div>
            <div>
                <h3 class="font-bold text-gray-800 text-base">Template Metadata</h3>
            </div>
            <div class="mt-auto flex items-center gap-1.5 text-sky-500 text-sm font-semibold group-hover:gap-2.5 transition-all">
                Pilih Jenis Ini <i class="fas fa-arrow-right text-xs"></i>
            </div>
        </a>
    </div>
</div>
@endsection