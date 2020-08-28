<?php

namespace App\Http\ViewComponents\ComponentLogic\ContentLinks;

use App\Http\ViewComponents\ComponentLogic\BaseComponentLogic;
use App\Services\ContentApi\Entities\Magazine;
use App\Services\ContentApi\Search;

class ContentLinkMagazineLogic extends BaseComponentLogic
{
    /**
     * @return array
     */
    public function get()
    {
        $magazine = cacheStuff('latestMagazine', 5, function () {
            if ($magazine = $this->getLastMagazine()) {
                return $magazine;
            }

            return null;
        });

        return [
            'magazine' => $magazine,
            'icon_image' => '/images/misc/device-icons.png',
        ];
    }

    private function getLastMagazine(): ?Magazine
    {
        $service = brandService();
        $search = $service->newSearch();

        $search->setTagIds([Magazine::ENTITY_TYPE_TAG_ID]);
        $search->setSort(Search::SORT_TYPE_LATEST);
        $search->setPageSize(1);
        $search->withContent();

        $magazine = $service->run($search)->hydrate()->first();

        return $magazine;
    }
}
