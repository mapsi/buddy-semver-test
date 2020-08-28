<?php

namespace App\Models\Event;

use Illuminate\Database\Eloquent\Model;
use App\Models\Interfaces\Importable as ImportableInterface;
use App\Models\Traits\Importable as ImportableTrait;
use App\Models\Interfaces\HasContentSections as HasContentSectionsInterface;
use App\Models\Traits\HasContentSections as HasContentSectionsTrait;
use App\Models\Interfaces\Publishable as PublishableInterface;
use App\Models\Traits\Publishable as PublishableTrait;
use Illuminate\Console\OutputStyle;
use DateTime;
use Illuminate\Support\Str;

class PreviousYear extends Model implements ImportableInterface, HasContentSectionsInterface, PublishableInterface
{
    use PublishableTrait;

    use ImportableTrait;
    use HasContentSectionsTrait;

    protected $guarded           = ['id'];
    public static function getEntityType()
    {
        return 'node';
    }
    public static function getEntityFields()
    {
        return array_merge(
            static::getContentSectionFields(),
            [

            ]
        );
    }
    public static function getEntityBundle()
    {
        return 'previous_year';
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function galleries()
    {
        return $this->belongsToMany(Gallery::class);
    }
    public function updateFromDrupal(array $entity, OutputStyle $output)
    {

        $this->fill([
            'title' => $entity['attributes']['title'] ?? null,
            'tab_title' => $entity['attributes']['field_tab_title'] ?? null,
            'intro' => $entity['attributes']['field_previous_year_intro']['value'] ?? null,
            'published_at' => $entity['attributes']['status'] ? new DateTime() : null,
            'slug' => Str::slug($entity['attributes']['title'] ?? time())
        ]);
        $a = $this->save();

        $fields = [
            'field_galleries' => [
                'class' => Gallery::class,
                'pivot' => []
            ],
        ];

        foreach ($fields as $field => $settings) {
            $this->syncImportField(
                $entity['relationships'][$field]['data'] ?? [],
                $settings['class'],
                $output,
                false,
                $settings['pivot'],
                $settings['detach'] ?? true,
                $settings['relationship'] ?? false
            );
        }

        return $a;
    }
}
