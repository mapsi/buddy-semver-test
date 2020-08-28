<?php

namespace App\Models;

use App\Models\Interfaces\Brandable as BrandableInterface;
use App\Models\Traits\BrandableTrait as BrandableTrait;
use Illuminate\Database\Eloquent\Model;

class Directory extends Model implements BrandableInterface
{
    use BrandableTrait;

    public $timestamps = false;

    protected $fillable = [
        'name',
        'slug',
        'base_uri',
        'type',
    ];

    protected $visible = [
        'name',
        'email',
        'jurisdictions',
        'individuals',
    ];

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function editorials()
    {
        return $this->hasManyThrough(DirectoryEditorial::class, DirectoryJurisdiction::class)->select('directory_editorials.*'); // This select stops the description being overwritten.
    }

    public function jurisdictions()
    {
        return $this->hasMany(DirectoryJurisdiction::class)->orderBy('region', 'asc')->orderBy('name', 'asc');
    }

    public function individuals()
    {
        return $this->hasMany(DirectoryIndividual::class);
    }

    public function filingStatistics()
    {
        return $this->hasMany(FilingStatisticsJurisdiction::class);
    }

    public function firms()
    {
        return $this->hasMany(DirectoryFirm::class);
    }

    /**
     * A directory is only non-free if it's a preview directory
     *
     * @return bool
     */
    public function isFree(): bool
    {
        return ! $this->preview;
    }

    public function listRankedIndividuals()
    {
        return $this
            ->individuals()
            ->whereHas('profile')
            ->whereHas('jurisdictions')
            ->orderBy('surname')
            ->orderBy('first_names')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->id => $item->surname . ', ' . $item->first_names];
            });
    }

    public function listRankedFirms()
    {
        return $this
            ->firms()
            ->whereHas('profile')
            ->whereHas('jurisdictions')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->id => $item->name];
            });
    }

    public function listGroupedJurisdictions()
    {
        return $this->jurisdictions
            ->groupBy('region')->map(function ($item) {
                return $item->pluck('name', 'id');
            });
    }

    /* Scopes */

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
