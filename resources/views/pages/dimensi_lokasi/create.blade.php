@extends('layouts.main')

@section('content')

<div class="py-6">

<a href="{{ route('dimensi_lokasi.index') }}"
    class="flex items-center font-semibold text-sky-600 ps-4 mb-4 hover:text-sky-900">
    <i class="fas fa-angle-left"></i> Kembali
</a>

{{-- WARNING DUPLIKASI --}}
@if(session('warning'))
    <div class="flex gap-4 bg-yellow-100 border border-yellow-400 text-lg text-yellow-800 px-4 py-6 rounded shadow">
        <div>
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div>
            {{ session('warning') }}
        </div>
    </div>
@endif

<div class="mt-2 bg-white rounded-md shadow p-6">

    <h1 class="text-xl font-bold text-gray-800 mb-6">
    Tambah Lokasi
    </h1>


        <form action="{{ route('dimensi_lokasi.store') }}" method="POST" class="space-y-6">
        @csrf

            <div>

                <label>Provinsi</label>

                <input type="text"
                value="BALI"
                class="w-full border rounded px-3 py-2 bg-gray-100"
                readonly>

            </div>


            <div>

            <label>Kabupaten</label>

                <select id="kabupaten" name="kabupaten" class="w-full border rounded px-3 py-2">
                <option value="">Pilih Kabupaten</option>
                </select>

            </div>


            <div>

                <label>Kecamatan</label>

                <select id="kecamatan" name="kecamatan" class="w-full border rounded px-3 py-2">
                <option value="">Pilih Kecamatan</option>
                </select>

            </div>


            <div>

                <label>Desa</label>

                <select id="desa" name="desa" class="w-full border rounded px-3 py-2">
                <option value="">Pilih Desa</option>
                </select>

            </div>


            <div>

                <label>Banjar</label>

                <input type="text" name="banjar"
                class="w-full border rounded px-3 py-2">

            </div>


            <div>

                <label>RT</label>

                <input type="text" name="rt"
                class="w-full border rounded px-3 py-2">

            </div>

            <input type="hidden" name="kode_kabupaten" id="kode_kabupaten">
            <input type="hidden" name="kode_kecamatan" id="kode_kecamatan">
            <input type="hidden" name="kode_desa" id="kode_desa">

            <div class="flex justify-end pt-4">
                <button type="submit"
                class="bg-sky-600 hover:bg-sky-700 text-white px-6 py-2 rounded-md shadow">
                Simpan
                </button>
            </div>

        </form>

    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded",function(){

fetch('/api/bali/kabupaten')
.then(res=>res.json())
.then(data=>{

let kab = document.getElementById('kabupaten');

data.forEach(item=>{
kab.innerHTML += `<option value="${item.nama}" data-kode="${item.kode}">${item.nama}</option>`;
});

});

});



document.getElementById('kabupaten').addEventListener('change',function(){

let selected = this.options[this.selectedIndex];
let kodeKab = selected.getAttribute('data-kode');

document.getElementById('kode_kabupaten').value = kodeKab;

fetch(`/api/bali/kecamatan?kab=${kodeKab}`)
.then(res=>res.json())
.then(data=>{

let kec = document.getElementById('kecamatan');

kec.innerHTML = '<option value="">Pilih Kecamatan</option>';

data.forEach(item=>{
kec.innerHTML += `<option value="${item.nama}" data-kode="${item.kode}">${item.nama}</option>`;
});

});

});



document.getElementById('kecamatan').addEventListener('change',function(){

let selectedKec = this.options[this.selectedIndex];
let kodeKec = selectedKec.getAttribute('data-kode');

document.getElementById('kode_kecamatan').value = kodeKec;

let selectedKab = document.getElementById('kabupaten').selectedOptions[0];
let kodeKab = selectedKab.getAttribute('data-kode');

fetch(`/api/bali/desa?kab=${kodeKab}&kec=${kodeKec}`)
.then(res=>res.json())
.then(data=>{

let desa = document.getElementById('desa');

desa.innerHTML = '<option value="">Pilih Desa</option>';

data.forEach(item=>{
desa.innerHTML += `<option value="${item.nama}" data-kode="${item.kode}">${item.nama}</option>`;
});

});

});

document.getElementById('desa').addEventListener('change',function(){

let selectedDes = this.options[this.selectedIndex];
let kodeDes = selectedDes.getAttribute('data-kode');

document.getElementById('kode_desa').value = kodeDes;

});
</script>

@endsection