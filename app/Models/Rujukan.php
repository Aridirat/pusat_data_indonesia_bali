<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rujukan extends Model
{
    protected $table = 'rujukan';
    protected $primaryKey = 'rujukan_id';
    public $timestamps = true;
    public $incrementing = true;

    protected $fillable = [
        'nama_rujukan',
        'gambar_rujukan',
        'link_rujukan',
        'produsen_id'
    ];

    public function produsen()
    {
        return $this->belongsTo(ProdusenData::class, 'produsen_id', 'produsen_id');
    }
}