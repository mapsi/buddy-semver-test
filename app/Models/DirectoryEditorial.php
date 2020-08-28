<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DirectoryEditorial extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'type',
        'name',
        'description',
    ];

    protected $visible = [
        'type',
        'name',
        'description',
    ];

    /* Scopes */

    public function jurisdiction()
    {
        return $this->belongsTo(DirectoryJurisdiction::class, 'directory_jurisdiction_id');
    }

    public function firm()
    {
        return $this->belongsTo(DirectoryFirm::class, 'directory_firm_id');
    }

    /* Mutators */

    public function getNameAttribute($name)
    {
        return $name ?: $this->firm->name;
    }
}
