<?php

namespace App\Http\ViewComposers;

use Illuminate\View\View;
use App\Models\Brand;
use App\Models\Directory;
use App\Models\DirectoryIndividual;

class FindExpertComposer
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
            $individuals = $directory->individuals()->hasProfile()->get();

            if ($individuals->isNotEmpty()) {
                $random_individual = $individuals->random();

                $data = [
                    'directory' => $directory,
                    'profile' => $random_individual->profile,
                    'individual' => $random_individual,
                    'image_url' => $random_individual->hasMedia('photo') ? $random_individual->getFirstMediaUrl('photo') : '/images/_dev/default_avatar.png',
                    'individual_url' => route('directories.individuals.show', [$directory, $random_individual->slug]),
                ];

                $view->with('data', $data);
            }
        } else {
            $view->with('data', null);
        }
    }
}
