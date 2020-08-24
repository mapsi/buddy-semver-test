<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class InternationalDirectoryJurisdiction extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
    ];

    protected $visible = [
        'name',
        'entries',
    ];

    /* Overrides */

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('alphabetically', function (Builder $builder) {
            $builder->orderBy('name', 'asc');
        });
    }

    /* Relations */

    public function entries()
    {
        return $this->hasMany(InternationalDirectoryEntry::class);
    }
}
