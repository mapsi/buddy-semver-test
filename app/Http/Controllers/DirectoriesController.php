<?php

namespace App\Http\Controllers;

use App\Models\Directory;

class DirectoriesController extends BaseDirectoryController
{
    public function index()
    {
        $directories = Directory::all();

        return view('directories.index', compact('directories'));
    }

    public function show(Directory $directory, $page = null)
    {
        $tagIds = $this->getTagIds($directory);
        return view('directories.show', compact('directory', 'page', 'tagIds'));
    }

    public function filingStatistics(Directory $directory)
    {
        $directory->load('filingStatistics.entries');

        return view('directories.filing-statistics', compact('directory'));
    }

    public function page(Directory $directory, $page)
    {
        $tagIds = $this->getTagIds($directory);
        return view('directories.show', compact('directory', 'page', 'tagIds'));
    }
}
