<?php

namespace App\Models;

use App\Models\Traits\DirectoryMapToTerms;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia\HasMedia;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;
use Spatie\MediaLibrary\Models\Media;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class DirectoryFirm extends Model implements HasMedia
{
    use HasMediaTrait;
    use HasSlug;
    use DirectoryMapToTerms;

    public $timestamps = false;
    protected $fillable = [
        'uuid',
        'name',
        'other_offices',
        'clients',
        'directory_id',
    ];
    protected $visible = [
        'name',
        'other_offices',
        'clients',
        'profile',
        'contacts',
    ];
    protected $casts = [
        'other_offices' => 'array',
        'clients' => 'array',
    ];

    public function isFree()
    {
        //default is true
        return $this->directory->isFree();
    }

    /* Relations */
    public function scopeSearchWiths($query)
    {
        $query->with('jurisdictions')->with('directory');
    }

    public function contacts()
    {
        return $this->hasMany(DirectoryContact::class)->orderBy('cardinality');
    }

    public function recommendations()
    {
        return $this->belongsToMany(DirectoryIndividual::class, 'directory_recommended_individuals');
    }

    public function profile()
    {
        return $this->morphOne(DirectoryProfile::class, 'rankable');
    }

    public function rankings()
    {
        return $this->morphMany(DirectoryRanking::class, 'rankable');
    }

    public function jurisdictions()
    {
        return $this->hasManyThrough(DirectoryJurisdiction::class, DirectoryRanking::class, 'rankable_id', 'id', 'id', 'directory_jurisdiction_id')->where('rankable_type', static::class)->distinct();
    }

    public function directory()
    {
        return $this->belongsTo(Directory::class);
    }

    /* Scopes */

    public function scopeAlphabetically($query)
    {
        return $query->orderBy('name');
    }

    public function scopeHasProfile($query)
    {
        return $query->has('profile');
    }

    public function scopeSearch($query, $search)
    {
        return $query->where('name', 'LIKE', '%' . $search . '%');
    }

    public function scopeStartsWith($query, $letter)
    {
        return $query->where('name', 'LIKE', $letter . '%');
    }

    /* Overrides */

    public function registerMediaCollections()
    {
        $this->addMediaCollection('logo')->singleFile()
            ->registerMediaConversions(function (Media $media) {
                $this->addMediaConversion('thumb')->width(200)->height(200);
            });
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom(['name'])
            ->saveSlugsTo('slug')
            ->allowDuplicateSlugs()
            ->doNotGenerateSlugsOnUpdate();
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function scopePromoteExtended($query)
    {
        return $query->withCount('profile')->orderBy('profile_count', 'desc');
    }
}
