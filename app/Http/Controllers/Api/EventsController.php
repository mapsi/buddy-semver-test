<?php

namespace App\Http\Controllers\Api;

use App\Models\Event\Event;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class EventsController
{
    /**
     * @param string $eventId
     * @return array
     */
    public function show($eventId)
    {
        $event = Event::withoutGlobalScopes()->find($eventId);

        return Arr::only($event->getAttributes(), 'title');
    }
}
