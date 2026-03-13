<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Location;
use Illuminate\Support\Facades\DB;

class LocationController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('location');

        if ($request->search) {
            $query->where(function($q) use ($request){
                $q->where('provinsi','like','%'.$request->search.'%')
                ->orWhere('kabupaten','like','%'.$request->search.'%')
                ->orWhere('kecamatan','like','%'.$request->search.'%')
                ->orWhere('desa','like','%'.$request->search.'%');
            });
        }

        $sortBy = $request->get('sort_by','kabupaten');
        $sortDir = $request->get('sort_dir','asc');

        $query->orderBy($sortBy,$sortDir);

        $data = $query->paginate(20)
                    ->onEachSide(1)
                    ->withQueryString();

        return view('pages.dimensi_lokasi.index', compact('data','sortBy','sortDir'));
    }

    public function create()
    {
        return view('pages.dimensi_lokasi.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'kabupaten' => 'required|string|max:100',
            'kecamatan' => 'required|string|max:100',
            'desa'      => 'required|string|max:100',

            'kode_kabupaten' => 'required',
            'kode_kecamatan' => 'required',
            'kode_desa'      => 'required',

            'banjar' => 'nullable|string|max:100',
            'rt'     => 'nullable|string|max:10',
        ]);

        $provinsi = "BALI";
        $kode_provinsi = "51";

        $kabupaten = strtoupper(trim($request->kabupaten));
        $kecamatan = strtoupper(trim($request->kecamatan));
        $desa      = strtoupper(trim($request->desa));
        $banjar    = strtoupper(trim($request->banjar));
        $rt        = strtoupper(trim($request->rt));


        /*
        CEK DUPLIKASI DATA
        */
        $duplicate = Location::where('kabupaten',$kabupaten)
                        ->where('kecamatan',$kecamatan)
                        ->where('desa',$desa)
                        ->where('banjar',$banjar)
                        ->where('rt',$rt)
                        ->first();

        if($duplicate){
            return redirect()
                ->back()
                ->withInput()
                ->with('warning','Data lokasi sudah terdaftar.');
        }

        Location::create([

            'kode_provinsi'  => $kode_provinsi,
            'kode_kabupaten' => $request->kode_kabupaten,
            'kode_kecamatan' => $request->kode_kecamatan,
            'kode_desa'      => $request->kode_desa,

            'provinsi'  => $provinsi,
            'kabupaten' => strtoupper($request->kabupaten),
            'kecamatan' => strtoupper($request->kecamatan),
            'desa'      => strtoupper($request->desa),
            'banjar'    => strtoupper($request->banjar),
            'rt'        => strtoupper($request->rt),
        ]);

        return redirect()
            ->route('dimensi_lokasi.index')
            ->with('success','Data lokasi berhasil ditambahkan.');
    }
}