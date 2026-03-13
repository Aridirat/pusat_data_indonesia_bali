<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
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