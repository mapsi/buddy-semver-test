<?php

namespace App\Http\Controllers;

use App\Models\Directory;
use App\Models\DirectoryFirm;
use App\Models\DirectoryIndividual;
use App\Models\DirectoryJurisdiction as Jurisdiction;

class DirectoryJurisdictionsController extends BaseDirectoryController
{
    public function index(Directory $directory)
    {
        if ($directory->type !== 'full') {
            abort(404);
        }

        return view('directories.jurisdictions.index', [
            'directory' => $directory,
            'tagIds' => $this->getTagIds($directory),
        ]);
    }

    public function show(Directory $directory, Jurisdiction $jurisdiction) // TODO: Check why implicit bindings are still being found for this.
    {
        $rankings = $jurisdiction->firms()->get();
        $individuals = $jurisdiction->individuals()->get();
        $editorials = $jurisdiction->editorials()->get();

        return view('directories.jurisdictions.show', [
            'directory' => $directory,
            'jurisdiction' => $jurisdiction,
            'data' => [
                'firms' => [
                    'rankings' => $rankings->where('type', 'firms')->groupBy('group_name'),
                    'editorials' => $editorials->where('type', 'firms'),
                    'individuals' => $individuals->where('type', 'individuals')->groupBy('group_name'),
                ],
                'barristers' => [
                    'rankings' => $rankings->where('type', 'barristers')->where('rankable_type', DirectoryFirm::class)->groupBy('group_name'),
                    'editorials' => $editorials->where('type', 'barristers'),
                    'individuals' => $individuals->where('type', 'barristers')->where('rankable_type', DirectoryIndividual::class)->groupBy('group_name'),
                ],
                'agencies' => [
                    'rankings' => $rankings->where('type', 'agencies')->groupBy('group_name'),
                    'editorials' => $editorials->where('type', 'agencies'),
                ],
            ],
        ]);
    }
}
