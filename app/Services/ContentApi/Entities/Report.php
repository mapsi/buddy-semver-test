<?php

namespace App\Services\ContentApi\Entities;

use App\Services\ContentApi\Traits\HasArticles;

class Report extends IsMagazine
{
    use HasArticles;

    const ENTITY_TYPE_TAG_ID = 58241;

    public function getView()
    {
        return 'reports.show';
    }
}
