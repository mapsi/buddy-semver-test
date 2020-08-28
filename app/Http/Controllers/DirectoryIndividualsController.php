<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Directory;
use App\Models\DirectoryIndividual;
use Illuminate\Http\Request;

class DirectoryIndividualsController extends BaseDirectoryController
{
    public function index(Request $request, Directory $directory, Brand $brand)
    {
        // This block is to stop people from sorting by any column they like.
        // I will let the package maintainer know at some point.
        if ($request->filled('sort')) {
            if (! in_array($request->get('sort'), DirectoryIndividual::$sortable)) {
                $request->query->remove('sort');
            }
        }
        $individuals = $directory->individuals()->with('profile')->with('media');
        if ($brand->machine_name == 'iam' || $directory->type == 'full') {
            $individuals->promoteExtended();
        }
        $individuals->sortable('full_name');

        // Starts with
        if ($request->filled('starts_with') && in_array($request->input('starts_with'), range('a', 'z'))) {
            $individuals->startsWith($request->input('starts_with'));
        }

        // Full directories include their jurisdiction
        if ($directory->type === 'full') {
            $individuals->with('jurisdictions');
        }
        if ($request->filled('sector')) {
            $individuals->whereHas('directorySectors', function ($query) use ($request) {
                $query->where('id', $request->input('sector'));
            });
        }
        // Different directory types have different views (Globe historical reasons)
        $view_path = 'directories.individuals.index';

        if ($directory->type === 'individuals') {
            $view_path = 'directories.individuals.index-300';
        }

        return view($view_path, [
            'tagIds' => $this->getTagIds($directory),
            'directory' => $directory,
            'individuals' => $individuals->paginate(),
        ]);
    }

    public function show(Directory $directory, DirectoryIndividual $individual)
    {
        return view('directories.individuals.show', compact('directory', 'individual'));
    }
}
