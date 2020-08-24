<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FilingStatisticsEntry extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'firm_name',
        'person_name',
        'rank',
    ];

    protected $visible = [
        'firm_name',
        'person_name',
        'rank',
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('order-by-rank', function (Builder $builder) {
            $builder->orderByRank();
        });
    }

    /* Relations */

    public function jurisdiction()
    {
        return $this->belongsTo(FilingStatisticsJurisdiction::class);
    }

    /* Scopes */

    public function scopeOrderByRank($query)
    {
        return $query->orderBy('rank');
    }

    /* Mutators */

    public function setFirmNameAttribute($value)
    {
        if (strpos($value, ' - ') !== false) {
            $value = substr($value, strrpos($value, ' - ') + 3);
        }

        $this->attributes['firm_name'] = trim($value);
    }
}
