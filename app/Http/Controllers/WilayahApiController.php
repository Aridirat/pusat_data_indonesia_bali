<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WilayahApiController extends Controller
{

    public function kabupaten()
    {
        $tahun = date('Y');

        $response = Http::get('https://sipedas.pertanian.go.id/api/wilayah/list_kab',[
            'thn'=>$tahun,
            'lvl'=>12,
            'pro'=>'51'
        ]);

        $data = $response->json()['output'] ?? [];

        $result = [];

        foreach($data as $kode=>$nama){
            $result[] = [
                'kode'=>$kode,
                'nama'=>$nama
            ];
        }

        return response()->json($result);
    }


    public function kecamatan(Request $request)
    {
        $tahun = date('Y');

        $response = Http::get('https://sipedas.pertanian.go.id/api/wilayah/list_kec',[
            'thn'=>$tahun,
            'lvl'=>13,
            'pro'=>'51',
            'kab'=>$request->kab
        ]);

        $data = $response->json()['output'] ?? [];

        $result = [];

        foreach($data as $kode=>$nama){
            $result[] = [
                'kode'=>$kode,
                'nama'=>$nama
            ];
        }

        return response()->json($result);
    }


    public function desa(Request $request)
    {
        $tahun = date('Y');

        $response = Http::get('https://sipedas.pertanian.go.id/api/wilayah/list_des',[
            'thn'=>$tahun,
            'lvl'=>13,
            'lv2'=>14,
            'pro'=>'51',
            'kab'=>$request->kab,
            'kec'=>$request->kec
        ]);

        $data = $response->json() ?? [];

        $result = [];

        foreach($data as $kode=>$nama){
            $result[] = [
                'kode'=>$kode,
                'nama'=>$nama
            ];
        }

        return response()->json($result);
    }

}