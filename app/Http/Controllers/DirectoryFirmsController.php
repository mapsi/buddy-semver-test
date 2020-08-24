<?php

namespace App\Http\Controllers;

use App\Models\Directory;
use App\Models\DirectoryFirm as Firm;
use Illuminate\Http\Request;

class DirectoryFirmsController extends BaseDirectoryController
{

    public function index(Request $request, Directory $directory)
    {
        if ($directory->type !== 'full') {
            abort(404);
        }

        $firms = $directory->firms()->promoteExtended()->alphabetically()->with([
            'profile', 'media', 'jurisdictions']);

        // Starts with
        if (
            $request->filled('starts_with') && in_array(
                $request->input('starts_with'),
                range('a', 'z')
            )
        ) {
            $firms->startsWith($request->input('starts_with'));
        }

        return view(
            'directories.firms.index',
            [
            'tagIds' => $this->getTagIds($directory),
            'directory' => $directory,
            'firms' => $firms->paginate()
            ]
        );
    }

    public function show(Directory $directory, Firm $firm)
    {
        $all                          = $firm->recommendations()->with('profile')->with('directory.jurisdictions')->with('jurisdictions')->with('media')->get();
        $recommendations_with_profile = $all->filter(function ($item) {
            return $item->profile != false;
        });
        $recommendations_without_profile = $all->filter(function ($item) {
            return $item->profile == false;
        });
        return view(
            'directories.firms.show',
            compact(
                'directory',
                'firm',
                'recommendations_with_profile',
                'recommendations_without_profile'
            )
        );
    }
}
