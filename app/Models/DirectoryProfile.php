<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DirectoryProfile extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'description',
        'website',
        'address',
        'email',
        'phone',
        'fax'
    ];

    protected $visible = [
        'description',
        'website',
        'address',
        'email',
        'phone',
        'fax'
    ];
}
