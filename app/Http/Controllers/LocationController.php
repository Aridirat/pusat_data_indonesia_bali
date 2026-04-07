<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Location;

class LocationController extends Controller
{
    public function index(Request $request)
    {
        $query = Location::query();

        if ($request->search) {
            $query->where(function($q) use ($request){
                $q->whereRaw('CAST(location_id AS CHAR) LIKE ?', ['%'.$request->search.'%'])
                ->orWhere('nama_wilayah','like','%'.$request->search.'%');
            });
        }

        $data = $query->paginate(20)
                    ->onEachSide(1)
                    ->withQueryString();

        return view('pages.dimensi_lokasi.index', compact('data'));
    }

    public function create()
    {
        return view('pages.dimensi_lokasi.create');
    }

    
    public function store(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'kode_provinsi'  => 'required|digits:2',
            'kode_kabupaten' => 'nullable|digits:2',
            'kode_kecamatan' => 'nullable|digits:3',
            'kode_desa'      => 'nullable|digits:3',
        ]);

        $kode_provinsi  = $request->kode_provinsi;
        $kode_kabupaten = $request->kode_kabupaten ?? '00';
        $kode_kecamatan = $request->kode_kecamatan ?? '000';
        $kode_desa      = $request->kode_desa ?? '000';

        $location_id = $kode_provinsi 
                    . $kode_kabupaten 
                    . $kode_kecamatan 
                    . $kode_desa;

        // FORMAT NAMA SESUAI LEVEL
        if ($request->desa) {
            $nama_wilayah = 'Desa ' . ucwords(strtolower($request->desa));

        } elseif ($request->kecamatan) {
            $nama_wilayah = 'Kecamatan ' . ucwords(strtolower($request->kecamatan));

        } elseif ($request->kabupaten) {

            $namaKabupaten = ucwords(strtolower($request->kabupaten));

            // Khusus Denpasar → Kota
            if (strtolower($request->kabupaten) === 'denpasar') {
                $nama_wilayah = 'Kota ' . $namaKabupaten;
            } else {
                $nama_wilayah = 'Kabupaten ' . $namaKabupaten;
            }

        } else {
            $nama_wilayah = 'Provinsi Bali';
        }

        if (Location::where('location_id', $location_id)->exists()) {
            return back()->withInput()->with('warning', 'Nama lokasi sudah terdaftar.');
        }

        Location::create([
            'location_id'  => $location_id,
            'nama_wilayah' => $nama_wilayah,
        ]);

        return redirect()
            ->route('dimensi_lokasi.index')
            ->with('success','Data lokasi berhasil ditambahkan.');
    }
}