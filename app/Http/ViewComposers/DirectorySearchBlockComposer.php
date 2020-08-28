<?php

namespace App\Http\ViewComposers;

use Illuminate\View\View;
use App\Models\Brand;

class DirectorySearchBlockComposer
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
        $directory = $view->getData()['directory'];
        if ($directory->type == 'full') {
            $jurisdictions_list = $directory->jurisdictions->mapWithKeys(function ($item) use ($directory) {
                return [route('directories.jurisdictions.show', [$directory, $item]) => $item['name']];
            })->sort();
            $view->with('jurisdictions_list', $jurisdictions_list);
        } else {
            $sector_list = \App\Models\DirectorySector::whereHas('directoryIndividuals', function ($query) use ($directory) {
                $query->where('directory_id', $directory->id);
            })->get()->mapWithKeys(function ($item) use ($directory) {
                return [route('directories.individuals.index', [$directory,'sector' => $item->id]) => $item->name];
            })->sort();
            $view->with('sector_list', $sector_list);
        }
    }
}
