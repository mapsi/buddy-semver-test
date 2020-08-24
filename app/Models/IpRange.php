<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IpRange extends Model
{
    public $fillable = [
        'name',
        'range',
        'user_id'
    ];
    function user()
    {
         return $this->belongsTo(User::class);
    }
}
