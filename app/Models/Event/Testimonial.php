<?php

namespace App\Models\Event;

use Illuminate\Database\Eloquent\Model;
use App\Models\Interfaces\Importable as ImportableInterface;
use App\Models\Traits\Importable as ImportableTrait;
use App\Models\Interfaces\Publishable as PublishableInterface;
use App\Models\Traits\Publishable as PublishableTrait;
use Illuminate\Console\OutputStyle;
use DateTime;
use Illuminate\Support\Str;

class Testimonial extends Model implements ImportableInterface, PublishableInterface
{
    use PublishableTrait;
    use ImportableTrait;

    protected $guarded           = ['id'];
    public static function getEntityType()
    {
        return 'node';
    }
    public static function getEntityBundle()
    {
        return 'testimonial';
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function speakers()
    {
        return $this->belongsToMany(Speaker::class);
    }
    public function sponsors()
    {
        return $this->belongsToMany(Sponsor::class);
    }
    public function updateFromDrupal(array $entity, OutputStyle $output)
    {

        $this->fill([
            'title' => $entity['attributes']['title'] ?? null,
            'name' => $entity['attributes']['field_testimonial_name'] ?? null,
            'job_title' => $entity['attributes']['field_testimonial_job_title'] ?? null,
            'type' => $entity['attributes']['field_testimonial_type'] ?? null,
            'video' => $entity['attributes']['field_testimonial_video']['video_id'] ?? null,
            'body' => $entity['attributes']['body']['value'] ?? null,
            'published_at' => $entity['attributes']['status'] ? new DateTime() : null,
            'slug' => Str::slug($entity['attributes']['title'] ?? time())
        ]);


        $s = $this->save();


        return $s;
    }
}
