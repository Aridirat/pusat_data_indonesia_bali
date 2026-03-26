<?php
// ─── app/Models/Time.php ───────────────────────────────────────
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Waktu extends Model
{
    protected $table      = 'time';
    protected $primaryKey = 'time_id';
    public $timestamps    = false;
    public $incrementing  = true;

    protected $fillable = ['decade', 'year', 'quarter', 'month', 'day'];

    public function data()
    {
        return $this->hasMany(Data::class, 'time_id', 'time_id');
    }

    public function getFullDateAttribute(): string
    {
        try {
            return \Carbon\Carbon::create($this->year, $this->month, $this->day)
                ->translatedFormat('d F Y');
        } catch (\Exception $e) {
            return '-';
        }
    }
}