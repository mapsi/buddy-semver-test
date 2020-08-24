<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DirectoryRanking extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'type',
        'group_order',
        'group_name',
        'rank',
        'description',
        'subname'
    ];

    protected $visible = [
        'name',
        'type',
        'group_order',
        'group_name',
        'rank',
        'rankable',
    ];

    /* Relations */

    public function jurisdiction()
    {
        return $this->belongsTo(DirectoryJurisdiction::class, 'directory_jurisdiction_id');
    }

    public function rankable()
    {
        return $this->morphTo();
    }

    /* Scopes */

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /* Mutators */

    public function getNameAttribute($name)
    {
        return $name ?: $this->rankable->name;
    }
}
