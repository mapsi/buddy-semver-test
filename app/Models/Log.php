<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    protected $guarded = ['id'];
    function loggable()
    {
        return $this->morphTo();
    }
    function user()
    {
        return $this->belongsTo(User::class);
    }
}