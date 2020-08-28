<?php

namespace App\Http\ViewComposers;

use Illuminate\View\View;
use App\Models\Brand;

class FeaturedFirmComposer
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
        if ($directory = \App\Models\Directory::where('type', 'full')->first()) {
            $firms = $directory->firms()->hasProfile()->get();

            if ($firms->isNotEmpty()) {
                $random_firm = $firms->random();

                $data = [
                    'directory' => $directory,
                    'firm' => $random_firm,
                    'image_url' => $random_firm->getFirstMediaUrl('logo') ? $random_firm->getFirstMediaUrl('logo') : '/images/_dev/office-buildings.svg',
                    'firm_url' => route('directories.firms.show', [$directory, $random_firm->slug]),
                ];

                $view->with('data', $data);
            }
        } else {
            $view->with('data', null);
        }
    }
}
