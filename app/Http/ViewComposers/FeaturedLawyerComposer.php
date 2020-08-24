<?php

namespace App\Http\ViewComposers;

use Illuminate\View\View;
use App\Models\Brand;
use App\Models\Directory;
use App\Models\DirectoryIndividual;

class FeaturedLawyerComposer
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
            $lawyers = $directory
                ->individuals()
                ->hasProfile()
                ->with('jurisdictions')
                ->where(function ($query) {
                    $query->where('position', 'like', '%' . 'partner' . '%')->orWhere('position', 'like', '%' . 'attorney' . '%');
                })
                ->get();

            if ($lawyers->isNotEmpty()) {
                $random_lawyer = $lawyers->random();

                $data = [
                    'directory' => $directory,
                    'lawyer' => $random_lawyer,
                    'image_url' => $random_lawyer->getFirstMediaUrl('photo') ? $random_lawyer->getFirstMediaUrl('photo') : '/images/_dev/default_avatar.png',
                    'individual_url' => route('directories.individuals.show', [$directory, $random_lawyer->slug]),
                ];

                $view->with('data', $data);
            }
        } else {
            $view->with('data', null);
        }
    }
}
