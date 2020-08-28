<?php

namespace App\Services\ContentApi\Entities;

use App\Services\ContentApi\Entities\Profile;
use App\Services\ContentApi\Entities\Section;
use App\Services\ContentApi\Search;
use App\Services\ContentApi\Traits\HasArticles;
use App\Services\ContentApi\Traits\HasPdf;
use App\Services\ContentApi\Traits\HasShopUrl;
use Illuminate\Support\Arr;

class Edition extends DataEntity
{
    use HasArticles;
    use HasPdf;
    use HasShopUrl;

    const ENTITY_TYPE_TAG_ID = 17669;

    // protected $intro;

    public function getName()
    {
        return $this->data['title'];
    }

    public function getDescription()
    {
        return $this->getPrecis();
    }

    public function getSeries(): Series
    {
        return $this->getRelations(Series::class)->first();
    }

    public function getPrecis()
    {
        return Arr::get($this->data, 'precis');
    }

    public function fetchProfiles(int $pageSize = 50)
    {
        $search = $this->service->newSearch();
        $search->setTagIds([Profile::ENTITY_TYPE_TAG_ID]);
        $search->setRelationIds([$this->getId()]);
        $search->setSort(Search::SORT_TYPE_TITLE);
        $search->setPageSize($pageSize);

        $result = $this->service->run($search)->hydrate();

        return $result;
    }

    public function fetchSections()
    {
        $search = $this->service->newSearch();
        $search->setTagIds([Section::ENTITY_TYPE_TAG_ID]);
        $search->setRelationIds([$this->getId()]);

        $result = $this->service->run($search)->hydrate();

        return $result->sortBy(function (Section $section) {
            return $section->getInfo('EditionOrder');
        });
    }

    public function isCurrent(): bool
    {
        return $this->getInfo()['Current'] ?? false;
    }

    public function getView()
    {
        return 'editions.show';
    }
}
