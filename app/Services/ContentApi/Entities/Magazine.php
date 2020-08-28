<?php

namespace App\Services\ContentApi\Entities;

use App\Services\ContentApi\TagGroups\Supplement;
use App\Services\ContentApi\Traits\HasArticles;
use App\Services\ContentApi\Traits\HasPdf;
use App\Services\ContentApi\Traits\HasShopUrl;
use Carbon\Carbon;

class Magazine extends IsMagazine
{
    use HasArticles;
    use HasPdf;
    use HasShopUrl;


    const ENTITY_TYPE_TAG_ID = 5553;
    const MAGAZINE_SECTION_INSIGHTS = 2831;

    public function getYear()
    {
        return Carbon::parse($this->getPublicationDate())->year;
    }

    public function getView()
    {
        return 'magazines.show';
    }
}
