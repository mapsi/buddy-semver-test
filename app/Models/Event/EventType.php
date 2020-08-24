<?php

namespace App\Models\Event;

use Illuminate\Database\Eloquent\Model;
use App\Models\Interfaces\Importable as ImportableInterface;
use App\Models\Traits\Importable as ImportableTrait;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Str;

class EventType extends Model implements ImportableInterface
{
    use ImportableTrait;

    protected $guarded = ['id'];
    public static function getEntityBundle()
    {
        return 'event_type';
    }
    public function getRouteKeyName()
    {
        return 'slug';
    }
    public function updateFromDrupal(array $entity, OutputStyle $output)
    {
        $this->fill([
            'name' => $entity['attributes']['name'],
            'slug' => Str::slug($entity['attributes']['name']),
        ]);

        return $this->save();
    }
    public function events()
    {
        return $this->hasMany(Event::class, 'type_id');
    }
}
