<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Data extends Model
{
    protected $table = 'data';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'metadata_id',
        'location_id',
        'time_id',
        'text_value',
        'number_value',
        'kategori_value',
        'other',
        'analisis_fenomena',
        'status',
        'date_inputed',
    ];

    protected $casts = [
        'date_inputed' => 'datetime',
        'number_value' => 'decimal:2',
    ];

    // ─── STATUS CONSTANTS ─────────────────────────────────────
    const STATUS_PENDING   = 0; // Belum diverifikasi
    const STATUS_AVAILABLE = 1; // Sudah diverifikasi, tampil di halaman utama
    const STATUS_REJECTED  = 2; // Ditolak admin

    // ─── RELASI ───────────────────────────────────────────────

    public function metadata()
    {
        return $this->belongsTo(Metadata::class, 'metadata_id', 'metadata_id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id', 'location_id');
    }

    public function time()
    {
        return $this->belongsTo(Waktu::class, 'time_id', 'time_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    // ─── SCOPE ────────────────────────────────────────────────

    /**
     * Hanya data yang sudah diverifikasi (available)
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', self::STATUS_AVAILABLE);
    }

    /**
     * Hanya data yang menunggu verifikasi
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ((int) $this->status) {
            self::STATUS_AVAILABLE => 'Available',
            self::STATUS_REJECTED  => 'Ditolak',
            default                => 'Pending',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ((int) $this->status) {
            self::STATUS_AVAILABLE => 'green',
            self::STATUS_REJECTED  => 'red',
            default                => 'yellow',
        };
    }
}