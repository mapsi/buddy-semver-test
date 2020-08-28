<?php

namespace App\Models;

use App\Models\Traits\DirectoryMapToTerms;
use Illuminate\Database\Eloquent\Model;
use Kyslik\ColumnSortable\Sortable;
use Spatie\MediaLibrary\HasMedia\HasMedia;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;
use Spatie\MediaLibrary\Models\Media;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * @property int    id
 * @property int    directory_id
 * @property string full_name
 * @property string slug
 * @property string uuid
 * @property string first_names
 * @property string surname
 * @property string gender
 * @property string firm_name
 * @property string position
 * @property string city
 * @property string country
 * @property string associations
 * @property string clients
 * @property string sectors
 * @property bool   is_operating
 */
class DirectoryIndividual extends Model implements HasMedia
{
    use HasMediaTrait;
    use HasSlug;
    use Sortable;
    use DirectoryMapToTerms;

    public $timestamps = false;
    public static $sortable = [
        'full_name',
        'firm_name',
        'country',
    ];
    protected $fillable = [
        'directory',
        'uuid',
        'first_names',
        'surname',
        'gender',
        'firm_name',
        'directory_firm_id',
        'position',
        'city',
        'country',
        'associations',
        'clients',
        'is_operating',
        'directory_id',
        'iamsays',
    ];
    protected $visible = [
        'directory',
        'first_names',
        'surname',
        'gender',
        'firm_name',
        'position',
        'city',
        'country',
        'associations',
        'clients',
        'profile',
        'is_operating',
        'iamsays',
    ];
    protected $casts = [
        'associations' => 'array',
        'clients' => 'array',
        'is_operating' => 'boolean',
    ];

    /**
     * @return array
     */

    public function scopeSearchWiths($query)
    {
        $query->with('media')->with('profile')->with('jurisdictions')->with('directory');
    }

    public function isFree()
    {
        return $this->directory->isFree();
    }

    public function directorySectors()
    {
        return $this->belongsToMany(DirectorySector::class, 'directory_individuals_sectors');
    }

    public function fullNameSortable($query, $direction)
    {
        return $query->orderBy('surname', $direction)->orderBy('first_names', $direction);
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom(['first_names', 'surname'])
            ->saveSlugsTo('slug')
            ->allowDuplicateSlugs()
            ->doNotGenerateSlugsOnUpdate();
    }

    public function registerMediaCollections()
    {
        $this->addMediaCollection('photo')->singleFile()
            ->registerMediaConversions(function (Media $media) {
                $this->addMediaConversion('thumb')->width(200)->height(200);
            });

        $this->addMediaCollection('firm_logo')->singleFile()
            ->registerMediaConversions(function (Media $media) {
                $this->addMediaConversion('thumb')->width(200)->height(200);
            });
    }

    /* Relations */

    public function recommendations()
    {
        return $this->belongsToMany(DirectoryFirm::class, 'directory_recommended_individuals');
    }

    public function profile()
    {
        return $this->morphOne(DirectoryProfile::class, 'rankable');
    }

    public function directory()
    {
        return $this->belongsTo(Directory::class);
    }

    public function jurisdictions()
    {
        return $this->hasManyThrough(
            DirectoryJurisdiction::class,
            DirectoryRanking::class,
            'rankable_id',
            'id',
            'id',
            'directory_jurisdiction_id'
        )
            ->where('rankable_type', static::class)
            ->distinct();
    }

    /* Scopes */

    public function scopeHasProfile($query)
    {
        return $query->has('profile');
    }

    public function scopePromoteExtended($query)
    {
        return $query->withCount('profile')->orderBy('profile_count', 'desc');
    }

    public function scopeSearch($query, $search)
    {
        return $query
            ->whereRaw(
                "CONCAT(directory_individuals.first_names, ' ', directory_individuals.surname) LIKE ?",
                ['%' . $search . '%']
            )
            ->orWhere('position', 'LIKE', '%' . $search . '%')
            ->orWhere('firm_name', 'LIKE', '%' . $search . '%');
    }

    public function scopeStartsWith($query, $letter)
    {
        return $query->where('surname', 'LIKE', $letter . '%');
    }

    /* Mutators */

    public function getFullNameAttribute()
    {
        return $this->first_names . ' ' . $this->surname;
    }

    public function getHasProfileAttribute()
    {
        return (bool)$this->profile;
    }
}
