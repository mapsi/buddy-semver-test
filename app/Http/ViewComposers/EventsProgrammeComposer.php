<?php

namespace App\Http\ViewComposers;

use Illuminate\View\View;
use App\Models\Brand;

class EventsProgrammeComposer
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
        $sessions = $view->event->sessions;

        $days = $sessions->sortBy('start_time')->groupBy(function ($session) {
            return $session->start_time->format('Y-m-d');
        });

        $view->with('programme', $days);
    }
}
