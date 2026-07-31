<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FiturKatalog extends Model
{
    protected $table      = 'fitur_katalog';
    protected $primaryKey = 'fitur_katalog_id';

    protected $fillable = ['nama_fitur'];
}