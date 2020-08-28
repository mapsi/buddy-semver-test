<?php

namespace App\Http\Controllers;

use App\Models\Directory;
use App\Services\ContentApi\TagGroups\DirectoryProduct;

abstract class BaseDirectoryController extends Controller
{
    protected function getTagIds(Directory $directory): string
    {
        $tagIds = [];

        if ($directory->slug === 'patent1000') {
            $tagIds[] = DirectoryProduct::TAG_ID_PATENT;
        }

        if ($directory->slug === 'strategy300') {
            $tagIds[] = DirectoryProduct::TAG_ID_STRATEGY;
        }

        if ($directory->slug === 'wtr1000') {
            $tagIds[] = DirectoryProduct::TAG_ID_WTR1000;
        }

        if ($directory->slug === 'wtr300') {
            $tagIds[] = DirectoryProduct::TAG_ID_WTR300;
        }

        return implode(',', $tagIds);
    }
}
