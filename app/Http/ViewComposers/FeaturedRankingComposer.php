<?php

namespace App\Http\ViewComposers;

use Illuminate\View\View;
use App\Models\Brand;
use App\Models\Directory;
use App\Models\DirectoryJurisdictions;

class FeaturedRankingComposer
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
            $rankings = $directory->jurisdictions()->get();

            if ($rankings->isNotEmpty()) {
                $random_ranking = $rankings->random();

                $data = [
                    'directory' => $directory,
                    'ranking' => $random_ranking,
                    'flag_class' => strtolower($random_ranking->flag_css_class),
                    'ranking_url' => route('directories.jurisdictions.show', [$directory, $random_ranking->slug]),
                ];

                $view->with('data', $data);
            }
        } else {
            $view->with('data', null);
        }
    }
}
