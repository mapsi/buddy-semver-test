<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia\HasMedia;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;
use Spatie\MediaLibrary\Models\Media;

class InternationalDirectoryEntry extends Model implements HasMedia
{
    use HasMediaTrait;

    public $timestamps = false;

    protected $dates = [
        'featured_from',
        'featured_to',
    ];

    protected $fillable = [
        'uuid',
        'name',
        'description',
        'address',
        'phone',
        'fax',
        'email',
        'website',
        'featured_from',
        'featured_to',
    ];

    protected $visible = [
        'name',
        'description',
        'address',
        'phone',
        'fax',
        'email',
        'website',
    ];

    /* Overrides */

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('alphabetically', function (Builder $builder) {
            $builder->orderBy('name', 'asc');
        });
    }

    public function registerMediaCollections()
    {
        $this->addMediaCollection('logo')
            ->singleFile()
            ->registerMediaConversions(function (Media $media) {
                $this->addMediaConversion('thumbnail')
                    ->sharpen(10);
            });
    }

    /* Relations */

    public function jurisdiction()
    {
        return $this->belongsTo(InternationalDirectoryJurisdiction::class, 'international_directory_jurisdiction_id');
    }

    /* Mutators */

    public function getNameWithJurisdictionAttribute()
    {
        return $this->name . ' (' . $this->jurisdiction->name . ')';
    }
}
