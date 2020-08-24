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
use DateTime;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Str;

class Speaker extends Model implements HasMedia, ImportableInterface, HasContentSectionsInterface, PublishableInterface
{
    use PublishableTrait;
    use HasContentSectionsTrait;
    use HasMediaTrait;
    use ImportableTrait;

    protected $guarded = ['id'];

    public static function getEntityType()
    {
        return 'node';
    }

    public static function getEntityBundle()
    {
        return 'speaker';
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

    public function testimonials()
    {
        return $this->belongsToMany(Testimonial::class);
    }

    public function sponsors()
    {
        return $this->belongsToMany(Sponsor::class);
    }

    public function sessions()
    {
        return $this->belongsToMany(Session::class);
    }

    public function updateFromDrupal(array $entity, OutputStyle $output)
    {
        //dd($entity);
        $this->fill([
            'title' => $entity['attributes']['title'] ?? null,
            'name' => $entity['attributes']['field_speaker_name'] ?? null,
            'job_title' => $entity['attributes']['field_speaker_job_title'] ?? null,
            'company' => $entity['attributes']['field_speaker_company'] ?? null,
            'body' => $entity['attributes']['body']['value'] ?? null,
            'contact' => $entity['attributes']['field_contact'] ?? null,
            'published_at' => $entity['attributes']['status'] ? new DateTime() : null,
            'featured' => $entity['attributes']['field_speaker_featured'],
            'slug' => Str::slug($entity['attributes']['title'] ?? time())
        ]);


        $s = $this->save();
        $this->syncImportField(
            $entity['relationships']['field_sponsors']['data'],
            Sponsor::class,
            $output
        );
        $this->syncImportField(
            $entity['relationships']['field_testimonials']['data'],
            Testimonial::class,
            $output
        );
        if (
            $featuredImagePath = $entity['relationships']['field_image']['data']['relationships']['field_media_image']['data']['attributes']['url']
                ?? null
        ) {
            $name = $entity['relationships']['field_image']['data']['attributes']['name'];

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
                'field_sponsors',
                'field_testimonials',
                'field_image',
                'field_image.field_media_image'
        ]);
    }
}
