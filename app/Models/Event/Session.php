<?php

namespace App\Models\Event;

use Illuminate\Database\Eloquent\Model;
use App\Models\Interfaces\Importable as ImportableInterface;
use App\Models\Traits\Importable as ImportableTrait;
use App\Models\Interfaces\Publishable as PublishableInterface;
use App\Models\Traits\Publishable as PublishableTrait;
use App\Models\Import;
use DateTime;
use Illuminate\Console\OutputStyle;

class Session extends Model implements ImportableInterface, PublishableInterface
{
    use PublishableTrait;

    use ImportableTrait;

    protected $guarded = ['id'];

    protected $dates = [
        'start_time',
        'end_time'
    ];

    public static function getEntityType()
    {
        return 'node';
    }

    public static function getEntityBundle()
    {
        return 'session';
    }

    public function speakers()
    {
        return $this->belongsToMany(Speaker::class);
    }

    public function sponsors()
    {
        return $this->belongsToMany(Sponsor::class);
    }

    public function moderator()
    {
        return $this->belongsTo(Speaker::class, 'moderator_id');
    }

    public function updateFromDrupal(array $entity, OutputStyle $output)
    {
        $start = $entity['attributes']['field_session_time']['value'] ?? null;
        $end = $entity['attributes']['field_session_time']['end_value'] ?? null;

        if ($start) {
            $start = new DateTime($start);
        }

        if ($end) {
            $end = new DateTime($end);
        }

        $this->fill([
            'title' => $entity['attributes']['title'] ?? null,
            'start_time' => $start,
            'end_time' => $end,
            'published_at' => $entity['attributes']['status'] ? new DateTime() : null,
            'description' => $entity['attributes']['body']['value'],
            'breakout' => $entity['attributes']['field_breakout']
        ]);

        if ($entity['relationships']['field_session_moderator']['data'] ?? null) {
            $field = Import::firstByUuidOrFail($entity['relationships']['field_session_moderator']['data']['id'])->importable;
            $this->moderator_id = $field->id;
        }
        $a = $this->save();
        $this->syncImportField($entity['relationships']['field_sponsors']['data'], Sponsor::class, $output, []);
        $this->syncImportField($entity['relationships']['field_speakers']['data'], Speaker::class, $output, []);

        return $a;
    }

    public static function getEntityFields()
    {
        return [
                'field_speakers',
                'field_sponsors',
                'field_session_moderator'
        ];
    }
}
