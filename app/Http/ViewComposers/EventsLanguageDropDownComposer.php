<?php

namespace App\Http\ViewComposers;

use Illuminate\View\View;
use App\Models\Brand;

class EventsLanguageDropDownComposer
{
    /**
     * The user repository implementation.
     *
     * @var UserRepository
     */

    /**
     * Create a new profile composer.
     *
     * @param  UserRepository  $users
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Bind data to the view.
     *
     * @param  View  $view
     * @return void
     */
    public function compose(View $view)
    {

        $events = \App\Models\Event\Event::where('slug', $view->event->slug)->where('type_id', $view->event->type_id)->get();
        $languages = [];

        if ($events->count() > 1) {
            foreach ($events as $event) {
                $languages[$event->id] = [
                    'language_code' => $event->language_code,
                    'language_label' => $event->language_label,
                    'base_path' => $event->getBasePath(),
                    'current' => false
                ];

                if ($event->id == $view->event->id) {
                    $languages[$event->id]['current'] = true;
                }
            }
        }

        $view->with('languages', collect($languages)->sortByDesc('current'));
    }
}
