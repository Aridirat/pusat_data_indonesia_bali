<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Group extends Model
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    
    protected $table = 'group';
    protected $primaryKey = 'group_id';
    public $timestamps = false;

    protected $fillable = [
        'title'
    ];

    public function user()
    {
        return $this->hasMany(User::class, 'group_id', 'group_id');
    }
}