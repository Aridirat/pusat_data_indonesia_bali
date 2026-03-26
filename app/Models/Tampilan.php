<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Tampilan extends Model
{
    protected $table      = 'tampilan';
    protected $primaryKey = 'tampilan_id';
    public $timestamps    = true;

    protected $fillable = ['nama_tampilan', 'user_id', 'filter_params'];

    protected $casts = [
        'filter_params' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function isiTampilan()
    {
        return $this->hasMany(IsiTampilan::class, 'tampilan_id', 'tampilan_id');
    }

    public function metadata()
    {
        return $this->belongsToMany(
            Metadata::class,
            'isi_tampilan',
            'tampilan_id',
            'metadata_id',
            'tampilan_id',
            'metadata_id'
        );
    }

    public function dataItems()
    {
        return $this->belongsToMany(
            Data::class,
            'tampilan_data',
            'tampilan_id',
            'id'
        );
    }
}