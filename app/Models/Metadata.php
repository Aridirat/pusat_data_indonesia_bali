<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Metadata extends Model
{
    protected $table      = 'metadata';
    protected $primaryKey = 'metadata_id';
    public $timestamps    = false;
    const STATUS_PENDING  = 1;
    const STATUS_ACTIVE   = 2;
    const STATUS_INACTIVE = 3;

    protected $fillable = [
        'nama',
        'alias',
        'konsep',
        'definisi',
        'klasifikasi',
        'asumsi',
        'metodologi',
        'penjelasan_metodologi',
        'tipe_data',
        'satuan_data',
        'tahun_mulai_data',          
        'frekuensi_penerbitan',
        'tahun_pertama_rilis',
        'bulan_pertama_rilis',
        'tanggal_rilis',
        'produsen_id',
        'nama_contact_person',
        'nomor_contact_person',
        'email_contact_person',
        'tag',
        'nama_rujukan',              
        'link_rujukan',              
        'gambar_rujukan',            
        'flag_desimal',
        'tipe_group',
        'group_by',
        'status',
        'date_inputed',
        'user_id',
    ];

    protected $casts = [
        'date_inputed'        => 'datetime',
        'bulan_pertama_rilis' => 'integer',
        'tanggal_rilis'       => 'integer',
        'tahun_pertama_rilis' => 'integer',
        'tipe_group'          => 'integer',
        'flag_desimal'        => 'integer',
        'status'              => 'integer',
        'produsen_id'         => 'integer',
        'group_by'            => 'integer',
        'user_id'             => 'integer',
    ];

    // ── RELASI ────────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function produsen()
    {
        return $this->belongsTo(ProdusenData::class, 'produsen_id', 'produsen_id');
    }

    public function groupParent()
    {
        return $this->belongsTo(Metadata::class, 'group_by', 'metadata_id');
    }

    
    public function groupChildren()
    {
        return $this->hasMany(Metadata::class, 'group_by', 'metadata_id');
    }

    public function data()
    {
        return $this->hasMany(Data::class, 'metadata_id', 'metadata_id');
    }

    // ── SCOPES ────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeInactive($query)
    {
        return $query->where('status', self::STATUS_INACTIVE);
    }

    // ── HELPERS ───────────────────────────────────────────────

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE   => 'Active',
            self::STATUS_INACTIVE => 'Inactive',
            default               => 'Pending',
        };
    }

    public function getStatusStyleAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE   => 'background:#dcfce7; color:#15803d;',
            self::STATUS_INACTIVE => 'background:#f3f4f6; color:#6b7280;',
            default               => 'background:#fef3c7; color:#b45309;',
        };
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}