<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class IsiTampilan extends Model
{
    protected $table      = 'isi_tampilan';
    protected $primaryKey = 'isi_tampilan_id';
    public $timestamps    = false;

    protected $fillable = ['tampilan_id', 'metadata_id'];

    public function tampilan()
    {
        return $this->belongsTo(Tampilan::class, 'tampilan_id', 'tampilan_id');
    }

    public function metadata()
    {
        return $this->belongsTo(Metadata::class, 'metadata_id', 'metadata_id');
    }
}