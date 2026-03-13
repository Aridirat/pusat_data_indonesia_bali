<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProdusenData extends Model
{
    protected $table = 'produsen_data';

    protected $primaryKey = 'produsen_id';

    protected $fillable = [
        'produsen_id',
        'nama_produsen',
        'kontak',
        'alamat'
    ];
}