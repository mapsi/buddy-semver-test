<?php

namespace App\Models\Event;

use Carbon\Carbon;
use App\Models\Interfaces\Importable as ImportableInterface;
use App\Models\Interfaces\Publishable as PublishableInterface;
use App\Models\Traits\Importable as ImportableTrait;
use App\Models\Traits\Publishable as PublishableTrait;
use DateTime;
use Illuminate\Console\OutputStyle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Spatie\MediaLibrary\Exceptions\FileCannotBeAdded\UnreachableUrl;
use Spatie\MediaLibrary\HasMedia\HasMedia;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;

/**
 * @property string title
 * @property string link
 * @property string link_text
 * @property string summary
 * @property Carbon published_at
 */
class Banner extends Model implements HasMedia, PublishableInterface, ImportableInterface
{
    use PublishableTrait;
    use HasMediaTrait;
    use ImportableTrait;

    /**
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * @return string
     */
    public static function getEntityType()
    {
        return 'node';
    }

    /**
     * @return string
     */
    public static function getEntityBundle()
    {
        return 'banner';
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return void
     */
    public function registerMediaCollections()
    {
        $this->addMediaCollection('banner')->singleFile();
    }

    /**
     * @param array       $entity
     * @param OutputStyle $output
     * @return bool
     * @throws \Spatie\MediaLibrary\Exceptions\FileCannotBeAdded
     */
    public function updateFromDrupal(array $entity, OutputStyle $output)
    {
        $this->fill([
            'title' => $entity['attributes']['title'] ?? null,
            'link' => $entity['attributes']['field_banner_link']['uri'] ?? null,
            'link_text' => $entity['attributes']['field_banner_link']['title'] ?? null,
            'summary' => $entity['attributes']['field_banner_summary_text']['value'] ?? null,
            'published_at' => $entity['attributes']['status'] ? new DateTime() : null,
        ]);

        $model = $this->save();

        $bannerImageData = Arr::get($entity, 'relationships.field_banner_image.data');
        $bannerImageUrl = Arr::get($bannerImageData, 'relationships.field_media_image.data.attributes.url');

        if ($bannerImageUrl) {
            try {
                $this->addMediaFromUrl($bannerImageUrl)
                    ->usingName(Arr::get($bannerImageData, 'attributes.name'))
                    ->toMediaCollection('banner');
            } catch (UnreachableUrl $exception) {
                $output->error($exception->getMessage());
            }
        } else {
            optional($this->getFirstMedia('banner'))->delete();
        }

        return $model;
    }

    /**
     * @return array
     */
    public static function getEntityFields()
    {
        return [
            'field_banner_image',
            'field_banner_image.field_media_image',
        ];
    }
}
