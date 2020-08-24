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

class Gallery extends Model implements HasMedia, ImportableInterface, PublishableInterface
{
    use PublishableTrait;
    use ImportableTrait;
    use HasMediaTrait;

    protected $guarded = ['id'];

    protected $dates = [
        'date'
    ];

    public static function getEntityType()
    {
        return 'node';
    }

    public static function getEntityBundle()
    {
        return 'gallery';
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function registerMediaCollections()
    {
        $this->addMediaCollection('images');
            /*->registerMediaConversions(function (Media $media) {
                $this->addMediaConversion('banner')->fit(Manipulations::FIT_CROP, 750, 450);
                $this->addMediaConversion('thumbnail')->fit(Manipulations::FIT_CROP, 190, 190);
                $this->addMediaConversion('letterbox')->fit(Manipulations::FIT_CROP, 355, 120);
            })*/;
    }

    public function previousYear()
    {
        return $this->hasMany(PreviousYear::class);
    }

    public static function getEntityFields()
    {
        return  [
                'field_gallery_image',
                'field_gallery_image.field_media_image'
        ];
    }

    public function updateFromDrupal(array $entity, OutputStyle $output)
    {
//dd(self::getEntityFields());

        $this->fill([
            'title' => $entity['attributes']['title'] ?? null,
            'heading' => $entity['attributes']['field_gallery_intro_heading'] ?? null,
            'body' => $entity['attributes']['field_gallery_intro_content'] ?? null,
            'date' => new DateTime($entity['attributes']['field_gallery_date']) ?? null,
            'published_at' => $entity['attributes']['status'] ? new DateTime() : null,
            'slug' => Str::slug($entity['attributes']['title'] ?? time())
        ]);

        $s = $this->save();
        $this->clearMediaCollection('images');

        if ($entity['relationships']['field_gallery_image']['data'][0]['relationships']['field_media_image']['data']['attributes']['url'] ?? null) {
            foreach ($entity['relationships']['field_gallery_image']['data'] as $image) {
                $path = $image['relationships']['field_media_image']['data']['attributes']['url'];
                $name = $image['attributes']['name'];

                try {
                    $this->addMediaFromUrl($path)->usingName($name)->toMediaCollection('images');
                } catch (UnreachableUrlUrl $exception) {
                    $output->error($exception->getMessage());
                }
            }
        }
        return $s;
    }
}
