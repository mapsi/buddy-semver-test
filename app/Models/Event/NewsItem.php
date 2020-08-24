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
use App\Models\Import;
use Illuminate\Support\Str;

class NewsItem extends Model implements ImportableInterface, HasContentSectionsInterface, PublishableInterface
{
    use PublishableTrait;
    use HasContentSectionsTrait;
    use ImportableTrait;

    protected $guarded           = ['id'];
    public static function getEntityType()
    {
        return 'node';
    }
    public static function getEntityBundle()
    {
        return 'event_news';
    }

    public function type()
    {
        return $this->belongsToMany(NewsType::class, 'type_id');
    }
    public function updateFromDrupal(array $entity, OutputStyle $output)
    {

        $this->fill([
            'title' => $entity['attributes']['title'] ?? null,
            'body' => $entity['attributes']['body']['value'] ?? null,
            'published_at' => $entity['attributes']['status'] ? new DateTime() : null,
            'gated' => $entity['attributes']['field_content_gated'] ?? false,
            'slug' => Str::slug($entity['attributes']['title'] ?? null)
        ]);

        if ($entity['relationships']['field_event_news_category']['data'] ?? null) {
            $field = Import::firstByUuidOrFail($entity['relationships']['field_event_news_category']['data']['id'])->importable;
            $this->type_id = $field->id;
        }
        return $this->save();
    }

    public static function getEntityFields()
    {
        return array_merge(static::getContentSectionFields(), [
                'field_event_news_category',
        ]);
    }
}
