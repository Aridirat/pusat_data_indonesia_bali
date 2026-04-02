<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $table = 'location';
    protected $primaryKey = 'location_id';
    public $incrementing = false;
    protected $keyType = 'integer';
    public $timestamps = false;

    protected $fillable = [
        'location_id',
        'nama_wilayah'
    ];

    public function getProvinsiCodeAttribute()
    {
        return substr($this->location_id ?? '', 0, 2);
    }

    public function getKabupatenCodeAttribute()
    {
        return substr($this->location_id ?? '', 2, 2);
    }

    public function getKecamatanCodeAttribute()
    {
        return substr($this->location_id ?? '', 4, 3);
    }

    public function getDesaCodeAttribute()
    {
        return substr($this->location_id ?? '', 6, 3);
    }

    public function getLevelAttribute()
    {
        if ($this->desa_code !== '000') return 'desa';
        if ($this->kecamatan_code !== '000') return 'kecamatan';
        if ($this->kabupaten_code !== '00') return 'kabupaten';
        return 'provinsi';
    }

    public function getLevelLabelAttribute()
    {
        return match ($this->level) {
            'provinsi' => 'Provinsi',
            'kabupaten' => 'Kabupaten',
            'kecamatan' => 'Kecamatan',
            'desa' => 'Desa',
            default => '-'
        };
    }

    public function getPrefixAttribute()
    {
        return match ($this->level) {
            'provinsi' => substr($this->location_id, 0, 2),
            'kabupaten' => substr($this->location_id, 0, 4),
            'kecamatan' => substr($this->location_id, 0, 7),
            'desa' => $this->location_id,
        };
    }
}