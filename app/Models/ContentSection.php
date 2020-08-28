<?php

namespace App\Models;

use DOMDocument;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Image\Manipulations;
use Spatie\MediaLibrary\Exceptions\FileCannotBeAdded\UnreachableUrl;
use Spatie\MediaLibrary\HasMedia\HasMedia;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;
use Spatie\MediaLibrary\Models\Media;
use Illuminate\Support\Arr;

class ContentSection extends Model implements HasMedia
{
    use HasMediaTrait {
        getFirstMediaUrl as originalGetFirstMediaUrl;
    }

    public $timestamps = false;

    protected $guarded = ['id', 'article_id'];

    protected $casts = ['data' => 'array'];

    protected $visible = [
        'type',
        'data',
        'group',
    ];

    protected static function boot()
    {
        parent::boot();

        // Always order by weight ascending.
        static::addGlobalScope('weight', function (Builder $builder) {
            $builder->orderBy('weight', 'asc');
        });

        // Make sure we're saving something to the JSON data field.
        static::saving(function ($model) {
            if (! $model->data) {
                $model->data = [];
            }
        });
    }

    /**
     * @param string $collectionName
     * @param string $conversionName
     * @return string
     */
    public function getFirstMediaUrl(string $collectionName = 'default', string $conversionName = ''): string
    {
        $imageUrl = $this->originalGetFirstMediaUrl($collectionName, $conversionName);

        if (! $imageUrl) {
            return Arr::get($this->data, $collectionName, '');
        }

        return $imageUrl;
    }

    public function fetchItem($field, $collection)
    {
        if (! array_key_exists($field, $this->data)) {
            return;
        }
        try {
            $customProperties = array_filter([
                'caption' => $this->data['caption'] ?? '',
                'credits' => $this->data['source'] ?? '',
            ]);
            $this->addMediaFromUrl($this->data[$field])
                ->withCustomProperties($customProperties)
                ->toMediaCollection($collection);
        } catch (UnreachableUrl $exception) {
            // TODO Log this?
        }
    }

    public function fetchImages()
    {
        $this->fetchItem('thumbnail', 'thumbnail');
        $this->fetchItem('image', 'image');
        $this->fetchItem('audio', 'audio');
        $this->fetchItem('video', 'video');
        $this->fetchItem('file', 'file');
        if (array_key_exists('text', $this->data)) {
            if ($new_markup = $this->fetchImagesInText('text')) {
                $data = $this->data;
                $data['text'] = $new_markup;

                $this->data = $data;
                $this->save();
            }
        }
    }

    /**
     * Finds <img> tags with the data-entity-uuid attribute and rewrites the paths to a new
     * location.
     *
     * @return string|null Returns the new markup if rewriting was needed, null if not.
     */
    public function fetchImagesInText(string $field_name)
    {
        $dom_document = new DOMDocument();
        @$dom_document->loadHTML('<?xml encoding="UTF-8">' . $this->data[$field_name], LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $dom_document->preserveWhiteSpace = false;
        $dom_document->formatOutput = true;

        foreach ($dom_document->childNodes as $item) {
            if ($item->nodeType == XML_PI_NODE) {
                $dom_document->removeChild($item); // Remove the hack
            }
        }

        $dom_document->encoding = 'UTF-8'; // Set the encoding

        // <a href
        $links = $dom_document->getElementsByTagName('a');

        foreach ($links as $element) {
            $old_href = $element->getAttribute('href');

            if (strpos($old_href, '/sites/default/files') !== 0) {
                continue;
            }

            $url = config('globemedia.drupal.url') . $old_href;

            // Sometimes we get files with massive filenames. This shortens them.
            $filename = pathinfo($old_href, PATHINFO_FILENAME);
            $filename = substr($filename, 0, 100);

            $basename = $filename . '.' . pathinfo($old_href, PATHINFO_EXTENSION);

            try {
                $media = $this
                    ->addMediaFromUrl($url)
                    ->usingName($filename)
                    ->usingFileName($basename)
                    ->toMediaCollection('embedded');

                $element->removeAttribute('data-entity-uuid');
                $element->setAttribute('href', $media->getFullUrl());
            } catch (UnreachableUrl $exception) {
                //
            }
        }

        // <img src
        $images = $dom_document->getElementsByTagName('img');

        foreach ($images as $element) {
            $old_src = $element->getAttribute('src');

            if (strpos($old_src, '/sites/default/files') !== 0) {
                continue;
            }

            $url = config('globemedia.drupal.url') . $old_src;

            try {
                $media = $this->addMediaFromUrl($url)->toMediaCollection('embedded');

                $element->removeAttribute('data-entity-uuid');
                $element->setAttribute('src', $media->getFullUrl());
            } catch (UnreachableUrl $exception) {
                //
            }
        }

        return $links->length || $images->length ? $dom_document->saveHTML() : null;
    }

    public function contentable()
    {
        return $this->morphTo();
    }

    public function registerMediaCollections()
    {
        $this->addMediaCollection('video')
            ->singleFile();
        $this->addMediaCollection('file')
            ->singleFile();
        $this->addMediaCollection('audio')
            ->singleFile();

        $this->addMediaCollection('image')
            ->singleFile()
            ->registerMediaConversions(function (Media $media) {
                $this->addMediaConversion('full')
                    ->fit(Manipulations::FIT_MAX, 640, 10000)
                    ->nonQueued();
            });

        $this->addMediaCollection('thumbnail')
            ->singleFile()
            ->registerMediaConversions(function (Media $media) {
                $this->addMediaConversion('full')
                    ->fit(Manipulations::FIT_MAX, 640, 10000)
                    ->nonQueued();
            });

        $this->addMediaCollection('embedded');
    }

    public static function newFromDrupal(array $entity)
    {
        $type = str_replace('paragraph--', '', $entity['type']);

        $content_section = new self(['type' => $type]);
        try {
            switch ($type) {
                case 'blockquote':
                    $content_section->data = [
                        'quote' => $entity['attributes']['field_para_quote'],
                        'citation' => $entity['attributes']['field_quote_citation'],
                    ];
                    break;
                case 'full_text':
                    $content_section->data = [
                        'text' => str_replace("<p>\n, basic_html</p>", '', $entity['attributes']['field_para_text']['processed']),
                    ];
                    break;
                case 'figure_with_caption':
                    $content_section->data = [
                        'title' => $entity['attributes']['field_para_fig_title'],
                        'image' => $entity['relationships']['field_para_image']['data']['relationships']['field_media_image']['data']['attributes']['url'],
                        'caption' => $entity['attributes']['field_para_caption'],
                        'source' => $entity['attributes']['field_para_fig_source'],
                    ];
                    break;
                case 'embed':
                    $content_section->data = [
                        'code' => $entity['attributes']['field_paragraph_embed_code'],
                    ];
                    break;
                case 'image_with_text':
                    $content_section->data = [
                        'direction' => ! empty($entity['attributes']['field_para_type']) ? $entity['attributes']['field_para_type'] : 'ltr',
                        'image' => $entity['relationships']['field_para_image']['data']['relationships']['field_media_image']['data']['attributes']['url'],
                        'text' => $entity['attributes']['field_para_text']['value'],
                        'caption' => $entity['attributes']['field_para_caption'],
                        'source' => $entity['attributes']['field_para_source'],
                    ];
                    break;
                case 'boxout':
                    $content_section->data = [
                        'title' => $entity['attributes']['field_para_boxout_title'],
                        'text' => $entity['attributes']['field_para_text']['processed'],
                    ];
                    break;
                case 'table_data':
                    $content_section->data = [
                        'table' => $entity['attributes']['field_para_text']['processed'],
                        'source' => $entity['attributes']['field_para_source'],
                        'caption' => $entity['attributes']['field_para_caption'],
                        'title' => $entity['attributes']['field_para_title'],
                    ];
                    break;
                case 'wallchart':
                    $content_section->data = [
                        //just in case its not realy json and somone has put somthing odd in there
                        'json' => json_encode(json_decode($entity['attributes']['field_json'])),
                    ];
                    break;
                case 'faq':
                    $content_section->data = [
                        'title' => $entity['attributes']['field_para_title'],
                        'text' => $entity['attributes']['field_para_text']['value'],
                        'topic' => $entity['relationships']['field_para_faq_topic']['data']['attributes']['name'] ?? '',
                    ];
                    break;
                case 'media_download':
                    $data = [
                        'gated' => $entity['attributes']['field_content_gated'],
                        'text' => $entity['attributes']['field_para_text']['value'],
                    ];

                    /*&
                     * $return[] = $settings['field'].'.field_para_media.field_media_file';
                    $return[] = $settings['field'].'.field_para_media.field_media_image';
                    $return[] = $settings['field'].'.field_para_media.field_media_audio_file';
                    $return[] = $settings['field'].'.field_para_media.field_media_video_file';
                     */

                    if (isset($entity['relationships']['field_para_thumbnail']['data']['relationships']['field_media_image']['data']['attributes']['url'])) {
                        $data['thumbnail'] = $entity['relationships']['field_para_thumbnail']['data']['relationships']['field_media_image']['data']['attributes']['url'];
                    }

                    if (isset($entity['relationships']['field_para_media']['data']['relationships']['field_media_video_file']['data']['attributes']['url'])) {
                        $data['audio'] = $entity['relationships']['field_para_media']['data']['relationships']['field_media_video_file']['data']['attributes']['url'];
                    }

                    if (isset($entity['relationships']['field_para_media']['data']['relationships']['field_media_audio_file']['data']['attributes']['url'])) {
                        $data['audio'] = $entity['relationships']['field_para_media']['data']['relationships']['field_media_audio_file']['data']['attributes']['url'];
                    }

                    if (isset($entity['relationships']['field_para_media']['data']['relationships']['field_media_file']['data']['attributes']['url'])) {
                        $data['file'] = $entity['relationships']['field_para_media']['data']['relationships']['field_media_file']['data']['attributes']['url'];
                    }

                    if (isset($entity['relationships']['field_para_media']['data']['relationships']['field_media_image']['data']['attributes']['url'])) {
                        $data['image'] = $entity['relationships']['field_para_media']['data']['relationships']['field_media_image']['data']['attributes']['url'];
                    }
                    $content_section->data = $data;

                    //dump($entity);
                    break;
                case 'kpi':
                    $content_section->data = [
                        'label' => $entity['attributes']['field_para_kpi_label'],
                        'value' => $entity['attributes']['field_para_kpi_value'],
                    ];
                    break;
            }
        } catch (\Exception $ex) {
            logger($ex->getMessage(), $entity);
        }

        return $content_section;
    }

    public function toString()
    {
        try {
            // Resolve the item into string form
            switch ($this->type) {
                case 'blockquote':
                    return $this->data['quote'] . ' ' . $this->data['citation'];
                case 'full_text':
                    return $this->data['text'];
                case 'figure_with_caption':
                    return $this->data['title'] . ' ' . $this->data['caption'];
                case 'embed':
                    return '';
                case 'image_with_text':
                    return $this->data['text'] . ' ' . $this->data['caption'] . ' ' . $this->data['source'];
                case 'boxout':
                    return $this->data['title'] . ' ' . $this->data['text'];
                case 'table_data':
                    return $this->data['table'] . ' ' . $this->data['source'];
            }
        } catch (\Exception $ex) {
            //echo $this->id;
        }

        return '';
    }

    public function views(): MorphMany
    {
        return $this->morphMany(\App\Models\View::class, 'routable');
    }
}
