<?php

namespace App\Services\ContentApi\Entities;

use App\Services\ContentApi\TagGroups\SeriesType;
use App\Services\ContentApi\Entities\Edition;
use App\Services\ContentApi\Search;
use Illuminate\Support\Collection;
use App\Services\ContentApi\Interfaces\Templatable;
use App\Services\ContentApi\Traits\HasTemplate;

class Series extends DataEntity implements Templatable
{
    use HasTemplate;

    const ENTITY_TYPE_TAG_ID = 17668;

    const TYPE_INSIGHT_TAG_ID = 69375;
    const TYPE_REVIEW_TAG_ID = 165555;
    const TYPE_GUIDE_TAG_ID = 165549;

    const CUSTOM_PERSON_PROFILE_A = 'Custom Person Profile A';

    private $editions;

    public function getTitle()
    {
        return $this->data['title'];
    }

    public function getView()
    {
        if ($this->getTemplate() == self::CUSTOM_PERSON_PROFILE_A) {
            return 'editions.edition_show_person_profile_blue';
        }

        return 'editions.edition_show_person_profile';
    }

    public function getTemplate(): string
    {
        return $this->getInfo('TemplateName');
    }

    public function getType(): SeriesType
    {
        return $this->getTagGroup(SeriesType::class)->first();
    }

    public function fetchEditions($sort = Search::SORT_TYPE_TITLE): Collection
    {
        if (! $this->editions) {
            $search = $this->service->newSearch();
            $search->setTagIds([Edition::ENTITY_TYPE_TAG_ID]);
            $search->setRelationIds([$this->getId()]);
            $search->setSort($sort);

            $result = $this->service->run($search)->hydrate();

            $this->editions = $result->reverse();
        }

        return $this->editions;
    }

    public function getCurrentEdition(): Edition
    {
        return $this->fetchEditions()->filter(function ($edition) {
            return $edition->isCurrent();
        })->first() ?? $this->fetchEditions()->first();
    }

    public function getPreviousEditions(Edition $currentEdition = null): Collection
    {
        return $this->fetchEditions()->filter(function ($edition) use ($currentEdition) {
            return ! $edition->isCurrent() && ($edition->getTitle() != $currentEdition->getTitle());
        });
    }

    public function getOtherEditions(Edition $excludedEdition): Collection
    {
        return $this->fetchEditions(Search::SORT_TYPE_LATEST)->reverse()->filter(function ($edition) use ($excludedEdition) {
            return $edition->getId() != $excludedEdition->getId();
        });
    }

    public function isParallel(): bool
    {
        return $this->getInfo("EditionLayout") == "parallel";
    }
}
