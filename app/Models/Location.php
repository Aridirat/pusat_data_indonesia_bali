<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $table = 'location';

    protected $primaryKey = 'location_id';

    public $timestamps = false;

    protected $fillable = [
        'kode_provinsi',
        'kode_kabupaten',
        'kode_kecamatan',
        'kode_desa',
        
        'provinsi',
        'kabupaten',
        'kecamatan',
        'desa',
        'banjar',
        'rt'
    ];
}