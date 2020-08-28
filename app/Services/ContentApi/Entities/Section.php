<?php

declare(strict_types=1);

namespace App\Services\ContentApi\Entities;

use App\Services\ContentApi\Entities\Profiles\OrganisationProfile;
use App\Services\ContentApi\Entities\Profiles\PersonProfile;
use App\Services\ContentApi\Entities\Article;
use App\Services\ContentApi\Service;
use Illuminate\Support\Collection;
use App\Services\ContentApi\Interfaces\Templatable;
use App\Services\ContentApi\Traits\HasTemplate;
use App\Services\ContentApi\Traits\HasAuthors;
use App\Services\ContentApi\Entities\Edition;

class Section extends DataEntity implements Templatable
{
    use HasTemplate;
    use HasAuthors;

    const ALPHABET_FILTER_TEMPLATE = 'Alphabet Filter';
    const PROFILE_LIST_TEMPLATE = 'Profile List';

    const ENTITY_TYPE_TAG_ID = 164785;
    private $contents;
    private $views = [
        self::ALPHABET_FILTER_TEMPLATE => '_partials.sections.alphabet_filter',
        self::PROFILE_LIST_TEMPLATE => '_partials.sections.profile_list',
    ];

    public function __construct(array $data, Service $service = null)
    {
        parent::__construct($data, $service);
        $this->contents = $this->fetchContents([], 300);
    }

    public function getContents()
    {
        return $this->contents;
    }

    /**
     * The method is used to make sure that the contents required are of a specific type
     * @param  array  $types Array containing strings of the required types
     * @return Collection
     */
    public function getContentsByType(array $types): Collection
    {
        return $this->contents->filter(function ($content) use ($types) {
            return in_array($content->getEntityType(), $types);
        })->values();
    }

    private function fetchContents(array $tagIds = [], int $pageSize = 100): Collection
    {
        $search = $this->service->newSearch();
        $search->setRelationIds([$this->getId()]);
        $search->setTagIds($tagIds);
        $search->setPageSize($pageSize);

        $result = $this->service->run($search)->hydrate()->sortBy(function (DataEntity $content) {
            return $content->getInfo('SectionOrder');
        })->values();

        return $result;
    }

    public function getEdition()
    {
        return $this->getRelations(Edition::class)->first();
    }

    public function getView()
    {
        return $this->views[$this->getTemplate()] ?? '_partials.sections.default';
    }
}
