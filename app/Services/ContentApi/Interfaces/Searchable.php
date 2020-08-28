<?php

namespace App\Services\ContentApi\Interfaces;

interface Searchable
{
    public function getHeadline();

    public function getSearchableArray();
}
