<?php

namespace App\Http\ViewComposers;

use Illuminate\View\View;
use App\Models\Brand;

class EventsPreviousYearGalleryComposer
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
        $galleries = $view->previous_year->galleries;

        $days = $galleries->sortBy('date')->groupBy(function ($gallery) {
            return $gallery->date->format('Y-m-d');
        });

        $view->with('gallery_days', $days);
    }
}
