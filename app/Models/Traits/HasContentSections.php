<?php

namespace App\Models\Traits;

use App\Models\ContentSection;
use Illuminate\Console\OutputStyle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\Exceptions\FileCannotBeAdded\UnreachableUrl;

/**
 * static::getContentSectionFields() needs adding to getEntityFields in something like
 * public static function getEntityFields()
    {
        return array_merge(static::getContentSectionFields(),
            [
        ]);
    }
 *
 */
trait HasContentSections
{
    public static function getGroups()
    {
        return  [
            'default' =>
                [
                    'field' => 'field_content',
                    'image' => true,
                    'file' => false,
                ]
            ];
    }
    public static function bootHasContentSections()
    {
        static::deleting(function ($model) {
            $model->contentSections->each->delete(); // Be sure to call their delete events so media can clear up.
        });
    }

    public function contentSectionsAll()
    {
        return $this->morphMany(ContentSection::class, 'contentable');
    }
    protected function contentSectionsGroup($group)
    {
        return $this->contentSectionsAll()->where('group', '=', $group);
    }
    public function contentSections()
    {
        return $this->contentSectionsAll()->whereNull('group');
    }
    public static function getContentSectionFields()
    {
        $return = [];
        foreach (self::getGroups() as $settings) {
                $return[] = $settings['field'] ;
            if (isset($settings['image']) && $settings['image']) {
                $return[] = $settings['field'] . '.field_para_image';
                $return[] = $settings['field'] . '.field_para_image.field_media_image';
            }
            if (isset($settings['thumbnail']) && $settings['thumbnail']) {
                $return[] = $settings['field'] . '.field_para_thumbnail';
                $return[] = $settings['field'] . '.field_para_thumbnail.field_media_image';
            }
            if (isset($settings['file']) && $settings['file']) {
                $return[] = $settings['field'] . '.field_para_media';
                $return[] = $settings['field'] . '.field_para_media.field_media_file';
                $return[] = $settings['field'] . '.field_para_media.field_media_image';
                $return[] = $settings['field'] . '.field_para_media.field_media_audio_file';
                $return[] = $settings['field'] . '.field_para_media.field_media_video_file';
            }
        }
        return $return;
    }
    public function attachContentSections(array $entity, OutputStyle $output)
    {
        if (! $this->wasRecentlyCreated) {
            $this->contentSectionsAll->each->delete();
        }

        foreach (self::getGroups() as $group => $settings) {
            if (! isset($settings['contentsection']) || $settings['contentsection']) {
                if (isset($entity['relationships'][$settings['field']]['data']['attributes'])) {
                    $content_sections = collect([$entity['relationships'][$settings['field']]['data']]);
                } else {
                    $data = collect($entity['relationships'][$settings['field']]['data']);


                    $content_sections  = $data->filter(function ($entity, $key) {
                        return isset($entity['attributes']); // TODO: Find out why some have no attributes tag.
                    });
                }
                $content_sections->map(function ($entity, $weight) use ($group) {
                    $content_section = ContentSection::newFromDrupal($entity);
                    $content_section->weight = $weight;
                    if ($group != 'default') {
                        $content_section->group = $group;
                    }
                    return $content_section;
                })
                ->each(function ($content_section) use ($output) {
                    try {
                        if ($this->hasContentSection($content_section)) {
                            return;
                        }
                        $this->contentSectionsAll()->save($content_section);
                    } catch (UnreachableUrl $exception) {
                        $output->error($exception->getMessage());
                    }

                    $content_section->fetchImages();
                });
            }
        }
    }

    public function contentAsString()
    {
        return $this->contentSections->map(function ($it) {
            return strip_tags($it->toString());
        })->implode(' ');
    }

    public function getHasContentSectionsAttribute()
    {
        return $this->contentSections->count() != 0;
    }

    /**
     * @param $content_section
     * @return bool
     */
    private function hasContentSection($content_section)
    {
        return ! ! $this->contentSectionsAll()->where('data', $content_section->attributes['data'])->first();
    }
}
