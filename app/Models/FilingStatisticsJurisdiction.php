<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FilingStatisticsJurisdiction extends Model
{
    const COUNTRY_CODES = [
        'Australia' => 'AU',
        'Benelux' => '', // Non-standard
        'Canada' => 'CA',
        'Czech Republic' => 'CZ',
        'Denmark' => 'DK',
        'EU Intellectual Property Office' => '', // Non-standard
        'Finland' => 'FI',
        'France' => 'FR',
        'Germany' => 'DE',
        'Hungary' => 'HU',
        'Italy' => 'IT',
        'Mexico' => 'MX',
        'Norway' => 'NO',
        'Romania' => 'RO',
        'Sweden' => 'SE',
        'Switzerland' => 'CH',
        'United Kingdom' => 'GB',
        'United States' => 'US',
        'WIPO' => '', // Non-standard
    ];

    public $timestamps = false;

    protected $fillable = ['name'];
    protected $visible = ['name'];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('order-alphabetically', function (Builder $builder) {
            $builder->orderAlphabetically();
        });
    }

    /* Relations */

    public function directory()
    {
        return $this->belongsTo(Directory::class);
    }

    public function entries()
    {
        return $this->hasMany(FilingStatisticsEntry::class);
    }

    /* Scopes */

    public function scopeOrderAlphabetically($query)
    {
        return $query->orderBy('name');
    }

    /* Mutators */

    public function getCountryCodeAttribute()
    {
        return static::COUNTRY_CODES[$this->name] ?? null;
    }
}
