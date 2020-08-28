<?php

namespace App\Services\ContentApi;

use App\Services\ContentApi\Entities\Article;
use App\Services\ContentApi\Entities\Author;
use App\Services\ContentApi\Entities\DirectoryArea;
use App\Services\ContentApi\Entities\DirectoryProfile;
use App\Services\ContentApi\Entities\DirectoryRanking;
use App\Services\ContentApi\Entities\Edition;
use App\Services\ContentApi\Entities\Firm;
use App\Services\ContentApi\Entities\Magazine;
use App\Services\ContentApi\Entities\Profiles\AuthorProfile;
use App\Services\ContentApi\Entities\Profiles\FirmProfile;
use App\Services\ContentApi\Entities\Profiles\OrganisationProfile;
use App\Services\ContentApi\Entities\Profiles\PersonProfile;
use App\Services\ContentApi\Entities\Profile;
use App\Services\ContentApi\Entities\Report;
use App\Services\ContentApi\Entities\Series;
use App\Services\ContentApi\Entities\Section;
use App\Services\ContentApi\Entities\QATopic;
use App\Services\ContentApi\Entities\QA;
use App\Services\ContentApi\Entities\QAJurisdiction;
use App\Services\ContentApi\Entities\Question;
use App\Services\ContentApi\Entities\Contributor;
use App\Services\ContentApi\Entities\Supplement;
use App\Services\ContentApi\TagGroups\EntityType;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class Results
{
    const AVAILABLE_ENTITIES = [
        Article::class,
        Author::class,
        DirectoryArea::class,
        DirectoryProfile::class,
        DirectoryRanking::class,
        Edition::class,
        Firm::class,
        Magazine::class,
        Report::class,
        Supplement::class,
        Series::class,
        Section::class,
        QATopic::class,
        QA::class,
        QAJurisdiction::class,
        Question::class,
        Contributor::class
    ];

    const AVAILABLE_FACETS = [
        TagGroups\ArticleType::class,
        TagGroups\Topic::class,
        TagGroups\WorkArea::class,
        TagGroups\Sector::class,
        TagGroups\Country::class,
        TagGroups\MagazineSection::class,
        TagGroups\DirectoryProduct::class,
    ];

    const AVAILABLE_PROFILES = [
        AuthorProfile::class,
        PersonProfile::class,
        OrganisationProfile::class,
        FirmProfile::class,
    ];

    protected $data = [];
    protected $search;

    public function __construct(array $data, Search $search = null)
    {
        $this->data = $data;
        $this->search = $search;
    }

    public function exclude(Arrayable ...$datasets)
    {
        $arrays = array_merge([], ...array_map(function ($item) {
            return $item->toArray();
        }, $datasets));

        $excludedIds = collect($arrays)->transform(function ($item) {
            return $item['id'];
        });

        $data = collect($this->data['items'])->whereNotIn('id', $excludedIds->toArray());

        return new Results(['items' => $data->toArray()]);
    }

    public function limit(int $limit)
    {
        $data = collect($this->data['items'])->slice(0, $limit);
        return new Results(['items' => $data->toArray()]);
    }

    public function paginate()
    {
        $articles = $this->hydrate();

        $options = [
            'path' => url()->current(),
        ];

        return new LengthAwarePaginator(
            $articles,
            $articles->count() ? $this->data['paging']['totalCount'] : 0,
            $this->search->getPageSize(),
            $this->search->getStartPage(),
            $options
        );
    }

    public function hydrate($service = null, $dataEntity = null)
    {
        // We need to inject the service back into the DataEntity for fetching of relations
        $service = $service ?? brandService();
        return collect(Arr::get($this->data, 'items', []))
            ->map(function (array $item) use ($service, $dataEntity) {
                if ($type = $this->getPresentableType($item['tags'], $dataEntity)) {
                    return new $type($item, $service);
                }

                return null;
            })
            ->filter();
    }

    public static function getPresentableType(array $tags, $dataEntity = null)
    {
        if (
            Arr::first($tags, function ($item) {
                return is_array($item) && (($item['typeId'] === EntityType::TAG_GROUP_ID)
                    && ($item['tagId'] === DirectoryProfile::ENTITY_TYPE_TAG_ID));
            })
        ) {
            return DirectoryProfile::class;
        }


        if (self::isProfile($tags)) {
            return self::profileClass($tags);
        }


        $entity = Arr::first($tags, function ($item) use ($dataEntity) {
            return ($item['typeId'] === EntityType::TAG_GROUP_ID && empty($dataEntity)) ||
                (! empty($dataEntity) && $dataEntity::ENTITY_TYPE_TAG_ID == $item['tagId']);
        });

        foreach (self::AVAILABLE_ENTITIES as $entityClass) {
            if ($entityClass::ENTITY_TYPE_TAG_ID === $entity['tagId']) {
                return $entityClass;
            }
        }
    }

    protected static function isProfile($tags)
    {
        $isProfile = Arr::first($tags, function ($item) {
            return ($item['typeId'] === EntityType::TAG_GROUP_ID)
                && ($item['tagId'] === Profile::ENTITY_TYPE_TAG_ID);
        });

        return $isProfile || false;
    }

    protected static function profileClass($tags)
    {
        foreach (self::AVAILABLE_PROFILES as $entityClass) {
            if (
                in_array($entityClass::PROFILE_TYPE_TAG_ID, array_map(function ($tag) {
                    return $tag['tagId'];
                }, $tags))
            ) {
                return $entityClass;
            }
        }
    }

    public function facetGroups()
    {
        $result = $this->data;

        $facetGroups = [];
        $resultFacetGroups = collect($result['facets'])
            ->whereIn('id', array_map(function ($item) {
                return $item::TAG_GROUP_ID;
            }, self::AVAILABLE_FACETS))
            ->transform(function ($group) {
                $facets = collect($group['facets']);

                return [
                    'id' => (int) $group['id'],
                    'name' => Search::TAG_GROUP_NAME[$group['id']],
                    'facets' => $facets->transform(function ($facet) {
                        return [
                            'count' => (int) $facet['count'],
                            'tagId' => (int) $facet['id'],
                            'name' => $facet['name'],
                        ];
                    }),
                ];
            });

        foreach (self::AVAILABLE_FACETS as $facetGroup) {
            if (
                $group = Arr::first($resultFacetGroups, function ($value) use ($facetGroup) {
                    return $value['id'] == $facetGroup::TAG_GROUP_ID;
                })
            ) {
                $facetGroups[] = $group;
            }
        }

        return $facetGroups;
    }

    public function getData()
    {
        return $this->data;
    }

    public function toArray()
    {
        return $this->data;
    }
}
