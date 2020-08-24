<?php

namespace App\Http\ViewComposers;

use Illuminate\View\View;
use App\Models\Brand;
use App\Models\Directory;
use App\Models\DirectoryIndividual;

class FeaturedLawyersComposer
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
        if ($directory = \App\Models\Directory::where('type', 'individuals')->first()) {
            $lawyers = $directory
                ->individuals()
                ->hasProfile()
                ->with('jurisdictions')

                ->get();
            $data = [];
            if ($lawyers->isNotEmpty()) {
                $random_lawyers = $lawyers->random(2);

                foreach ($random_lawyers as $random_lawyer) {
                    $data[] = [
                        'directory' => $directory,
                        'lawyer' => $random_lawyer,
                        'image_url' => $random_lawyer->getFirstMediaUrl('photo') ? $random_lawyer->getFirstMediaUrl('photo') : '/images/_dev/default_avatar.png',
                        'individual_url' => route('directories.individuals.show', [$directory, $random_lawyer->slug]),
                    ];
                }
            }
            $view->with('data', $data);
        } else {
            $view->with('data', null);
        }
    }
}
