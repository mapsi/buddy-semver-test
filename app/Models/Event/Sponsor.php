<?php

namespace App\Models\Event;

use Illuminate\Database\Eloquent\Model;
use Spatie\Image\Manipulations;
use Spatie\MediaLibrary\Exceptions\FileCannotBeAdded\UnreachableUrl;
use Spatie\MediaLibrary\File;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;
use Spatie\MediaLibrary\HasMedia\HasMedia;
use App\Models\Interfaces\Importable as ImportableInterface;
use App\Models\Traits\Importable as ImportableTrait;
use App\Models\Interfaces\HasContentSections as HasContentSectionsInterface;
use App\Models\Traits\HasContentSections as HasContentSectionsTrait;
use App\Models\Interfaces\Publishable as PublishableInterface;
use App\Models\Traits\Publishable as PublishableTrait;
use Illuminate\Console\OutputStyle;
use DateTime;
use Illuminate\Support\Str;

class Sponsor extends Model implements HasMedia, ImportableInterface, HasContentSectionsInterface, PublishableInterface
{
    use PublishableTrait;
    use HasMediaTrait;
    use HasContentSectionsTrait;
    use ImportableTrait;

    protected $guarded = ['id'];

    public static function getEntityType()
    {
        return 'node';
    }
    public static function getEntityBundle()
    {
        return 'sponsor';
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function registerMediaCollections()
    {
        $this->addMediaCollection('image')
            ->singleFile();
            /*->registerMediaConversions(function (Media $media) {
                $this->addMediaConversion('banner')->fit(Manipulations::FIT_CROP, 750, 450);
                $this->addMediaConversion('thumbnail')->fit(Manipulations::FIT_CROP, 190, 190);
                $this->addMediaConversion('letterbox')->fit(Manipulations::FIT_CROP, 355, 120);
            })*/;
    }

    public function speakers()
    {
        return $this->belongsToMany(Speaker::class);
    }

    public function sessions()
    {
        return $this->belongsToMany(Session::class);
    }

    public function scopeTier($query, $tier)
    {
        return $query->rightJoin('sponsor_tiers', 'event_sponsor.tier_id', '=', 'sponsor_tiers.id')
            ->where('sponsor_tiers.name', '=', $tier);
    }

    public function testimonials()
    {
        return $this->belongsToMany(Testimonial::class);
    }


    public function updateFromDrupal(array $entity, OutputStyle $output)
    {

        $this->fill([
           'title' => $entity['attributes']['title'] ?? null,
           'tagline' => $entity['attributes']['field_sponsor_tagline'] ?? null,
           'contact' => $entity['attributes']['field_contact'] ?? null,
           'website' => $entity['attributes']['field_website']['uri'] ?? null,
           'bio' => $entity['attributes']['body']['value'] ?? '',
           'published_at' => $entity['attributes']['status'] ? new DateTime() : null,
           'slug' => Str::slug($entity['attributes']['title'] ?? time())
        ]);


        $s = $this->save();

        $this->syncImportField(
            $entity['relationships']['field_testimonials']['data'],
            Testimonial::class,
            $output
        );
        if (
            $featuredImagePath = $entity['relationships']['field_sponsor_image']['data']['relationships']['field_media_image']['data']['attributes']['url']
               ?? null
        ) {
            $name = $entity['relationships']['field_sponsor_image']['data']['attributes']['name'];

            try {
                $this->addMediaFromUrl($featuredImagePath)->usingName($name)->toMediaCollection('image');
            } catch (UnreachableUrl $exception) {
                $output->error($exception->getMessage());
            }
        }

        return $s;
    }

    public static function getEntityFields()
    {
        return array_merge(static::getContentSectionFields(), [
                'field_testimonials',
                'field_sponsor_image',
                'field_sponsor_image.field_media_image'
        ]);
    }
}
