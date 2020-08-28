<?php

namespace App\Services\ContentApi;

use App\Services\ContentApi\Entities\Profile;
use App\Services\ContentApi\Entities\Profiles\AuthorProfile;
use App\Services\ContentApi\Entities\Profiles\FirmProfile;
use App\Services\ContentApi\TagGroups\ArticleCategory;
use App\Services\ContentApi\TagGroups\ArticleType;
use App\Services\ContentApi\TagGroups\AuthorType;
use App\Services\ContentApi\TagGroups\Brand;
use App\Services\ContentApi\TagGroups\Country;
use App\Services\ContentApi\TagGroups\DirectoryProduct;
use App\Services\ContentApi\TagGroups\DirectoryProfileCategory;
use App\Services\ContentApi\TagGroups\DirectoryProfileType;
use App\Services\ContentApi\TagGroups\DirectorySector;
use App\Services\ContentApi\TagGroups\ProfileType;
use App\Services\ContentApi\TagGroups\DirectoryYear;
use App\Services\ContentApi\TagGroups\EntityType;
use App\Services\ContentApi\TagGroups\FirmContributorType;
use App\Services\ContentApi\TagGroups\IndustryJurisdiction;
use App\Services\ContentApi\TagGroups\LexologyJurisdiction;
use App\Services\ContentApi\TagGroups\LexologySerachableType;
use App\Services\ContentApi\TagGroups\MagazineSection;
use App\Services\ContentApi\TagGroups\Sector;
use App\Services\ContentApi\TagGroups\SeriesType;
use App\Services\ContentApi\TagGroups\SubBrand;
use App\Services\ContentApi\TagGroups\SupplementIssue;
use App\Services\ContentApi\TagGroups\Topic;
use App\Services\ContentApi\TagGroups\WorkArea;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class Service
{
    const INDEX_BULKACTIONS_REINDEX = 1;
    const INDEX_BULKACTIONS_DELETE = 2;

    public const SOURCE_ID_ARTICLE = 1;
    public const SOURCE_ID_AUTHOR = 5;
    public const SOURCE_ID_FIRM = 8;
    public const SOURCE_ID_MAGAZINE = 9;
    public const SOURCE_ID_GDR_ARTICLE = 20;
    public const SOURCE_ID_SERIES = 21;
    public const SOURCE_ID_EDITION = 22;
    public const SOURCE_ID_REPORT = 23;

    public static $sourceIdTypes = [
        self::SOURCE_ID_ARTICLE => "DrupalArticle",
        self::SOURCE_ID_MAGAZINE => "DrupalMagazine",
        self::SOURCE_ID_AUTHOR => "DrupalAuthor",
        // self::DrupalProfile = "DrupalProfile",
        self::SOURCE_ID_FIRM => "DrupalFirm",
        self::SOURCE_ID_SERIES => "DrupalSeries",
        self::SOURCE_ID_EDITION => "DrupalEdition",
        self::SOURCE_ID_REPORT => "DrupalReport",
    ];

    public static $sourceTypes = [
        self::SOURCE_ID_ARTICLE => "article",
        self::SOURCE_ID_MAGAZINE => "magazine",
        self::SOURCE_ID_AUTHOR => "author_profile",
        self::SOURCE_ID_FIRM => "firm",
        self::SOURCE_ID_SERIES => "series",
        self::SOURCE_ID_EDITION => "edition",
        self::SOURCE_ID_REPORT => "report",
    ];

    protected $client;
    protected $baseUri;
    protected $apiKey;

    public function __construct(string $baseUri, string $apiKey)
    {
        $this->baseUri = $baseUri;
        $this->apiKey = $apiKey;

        $this->connect();
    }

    private function connect()
    {
        $this->client = new Client([
            'base_uri' => $this->baseUri,
            'headers' => [
                'API_KEY' => $this->apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    public static function generateId($sourceId, $id)
    {
        return strtoupper(sha1(utf8_encode(self::$sourceIdTypes[$sourceId] . '-' . $id)));
    }

    public function getContent($contentId, $querySource = 0)
    {
        // Query source values
        // WithContent = 0,
        // WithoutContent = 1,
        // SummaryOnly = 2,
        // WithEmailPrecis = 3

        $result = $this->makeRequest('GET', "content/{$contentId}?querySource={$querySource}");

        return (new Results(['items' => [$result]]))->hydrate($this)->first();
    }

    public function getBulkByIds(array $contentIds, $querySource = 1)
    {
        $results = $this->makeRequest('POST', "content/bulkbyid?querySource={$querySource}", ['json' => $contentIds]);

        return (new Results($results));
    }

    public function ingest($type, $uuid)
    {
        $sourceId = array_search($type, Service::$sourceTypes);

        $payload = [
            'json' => [
                [
                    "action" => self::INDEX_BULKACTIONS_REINDEX,
                    "sourceId" => $sourceId,
                    "originalId" => $uuid,
                ]
            ],
        ];

        $response = $this->makeRequest('POST', 'index/_bulkactions?immediate=true', $payload);

        return $this->getContent(self::generateId($sourceId, $uuid));
    }

    protected function makeRequest($method, $url, $payload = [])
    {
        $baseUri = $this->baseUri;
        $apiKey = $this->apiKey;

        logger("Making request to '{$url}'", compact('baseUri', 'apiKey', 'method', 'payload'));
        $response = $this->client->request($method, $url, $payload);

        $contents = $response->getBody()->getContents();
        $result = json_decode($contents, true);
        logger('The response was...', compact('result'));

        return $result;
    }

    public function getContentBySourceLink(string $sourceLink, $dataEntity = null)
    {
        $result = $this->findContentBySourceLink($sourceLink, $dataEntity);

        if (! $result) {
            abort(404);
        }

        return $result;
    }

    public function findContentBySourceLink(string $sourceLink, $dataEntity = null)
    {
        $search = $this->newSearch()->withContent();

        $lexprefix = "pro/content/";

        if (substr($sourceLink, 0, strlen(($lexprefix))) === $lexprefix) {
            $slug = substr($sourceLink, strlen($lexprefix) - strlen($sourceLink));

            $search->setSlug($slug);
        } else {
            $search->setSourceLink(get_host_config('prod_url') . $sourceLink);
        }

        $result = $this->run($search)->hydrate(null, $dataEntity);

        return $result->first();
    }

    public function newSearch()
    {
        return new Search();
    }

    protected static function collectTagGroups(self $service = null)
    {
        return cacheStuff(__METHOD__, 60, function () use ($service) {
            if (is_null($service)) {
                $service = brandService();
            }

            return collect()
                ->concat(cacheGroupTags(ArticleCategory::class, $service))
                ->concat(cacheGroupTags(ArticleType::class, $service))
                ->concat(cacheGroupTags(AuthorType::class, $service))
                ->concat(cacheGroupTags(Brand::class, $service))
                ->concat(cacheGroupTags(Country::class, $service))
                ->concat(cacheGroupTags(DirectoryProduct::class, $service))
                ->concat(cacheGroupTags(DirectoryProfileCategory::class, $service))
                ->concat(cacheGroupTags(DirectoryProfileType::class, $service))
                ->concat(cacheGroupTags(DirectorySector::class, $service))
                ->concat(cacheGroupTags(DirectoryYear::class, $service))
                ->concat(cacheGroupTags(EntityType::class, $service))
                ->concat(cacheGroupTags(FirmContributorType::class, $service))
                ->concat(cacheGroupTags(Topic::class, $service))
                ->concat(cacheGroupTags(IndustryJurisdiction::class, $service))
                ->concat(cacheGroupTags(LexologyJurisdiction::class, $service))
                ->concat(cacheGroupTags(MagazineSection::class, $service))
                ->concat(cacheGroupTags(ProfileType::class, $service))
                ->concat(cacheGroupTags(Sector::class, $service))
                ->concat(cacheGroupTags(SeriesType::class, $service))
                ->concat(cacheGroupTags(SubBrand::class, $service))
                ->concat(cacheGroupTags(SupplementIssue::class, $service))
                ->concat(cacheGroupTags(LexologySerachableType::class, $service))
                ->concat(cacheGroupTags(WorkArea::class, $service));
        });
    }

    protected static $groupTagsByTagId;

    public static function getGroupTagsByTagId($tagId, $service = null)
    {
        $service = $service ?? brandService();
        if (is_null(self::$groupTagsByTagId)) {
            self::$groupTagsByTagId = cacheStuff(__METHOD__, 60, function () use ($service) {
                return self::collectTagGroups($service)->keyBy('tagId');
            });
        }

        return Arr::get(self::$groupTagsByTagId, $tagId);
    }

    protected function resolveTagIds(array &$tags)
    {
        foreach ($tags as $tagKey => $tagId) {
            if ($tag = self::getGroupTagsByTagId($tagId, $this)) {
                $tags[$tagKey] = $tag;
            }
        }
    }

    public function fetchAuthorProfileByAuthorId($authorId)
    {
        $profile = cacheStuff(__METHOD__ . $authorId, 60, function () use ($authorId) {
            $tagIds = [
                Profile::ENTITY_TYPE_TAG_ID,
                AuthorProfile::PROFILE_TYPE_TAG_ID,
            ];

            $service = brandService();
            $search = $service->newSearch();
            $search->setTagIds($tagIds);
            $search->setRelationIds([$authorId]);

            return $service->run($search)->hydrate()->first();
        });

        return $profile;
    }

    public function fetchFirmProfileByFirmId($firmId)
    {
        $profile = cacheStuff(__METHOD__ . $firmId, 60, function () use ($firmId) {
            $tagIds = [
                Profile::ENTITY_TYPE_TAG_ID,
                FirmProfile::PROFILE_TYPE_TAG_ID,
            ];

            $service = brandService();
            $search = $service->newSearch();
            $search->setTagIds($tagIds);
            $search->setRelationIds([$firmId]);

            return $service->run($search)->hydrate()->first();
        });

        return $profile;
    }

    public function run(Search $search)
    {
        $payload = $search->format();
        $results = $this->makeRequest('POST', 'content/searchv3', $payload);

        // results are compressed, let's expand the relations
        if ($relations = Arr::pull($results, 'relations')) {
            foreach ($relations as $relKey => $relValue) {
                if (isset($relValue['tags']) && (isset($relValue['tags'][0])) && (! is_array($relValue['tags'][0]))) {
                    $this->resolveTagIds($relations[$relKey]['tags']);
                }
            }

            foreach ($results['items'] as $key => $item) {
                foreach ($item['relations'] as $subKey => $relationId) {
                    $results['items'][$key]['relations'][$subKey] = Arr::first($relations, function ($relation) use ($relationId) {
                        return $relation['id'] === $relationId;
                    });
                }
            }
        }

        foreach ($results['items'] as $key => $value) {
            if (isset($value['tags']) && (isset($value['tags'][0])) && (! is_array($value['tags'][0]))) {
                $this->resolveTagIds($results['items'][$key]['tags']);
            }
        }

        return new Results($results, $search);
    }

    public function getTagGroups()
    {
        return $this->makeRequest('GET', 'taggroups');
    }

    public function getTagGroupTags(int $tagGroupId)
    {
        return $this->makeRequest('GET', "taggroups/{$tagGroupId}/tags");
    }

    public function getTag(int $tagId)
    {
        return $this->makeRequest('GET', "tags/{$tagId}");
    }

    public function __sleep()
    {
        return ['baseUri', 'apiKey'];
    }

    public function __wakeup()
    {
        $this->connect();
    }

    public static function fetchTrending(int $relateByTagId): Collection
    {
        $key = active_host() . '_trending_articles_for_' . $relateByTagId;

        if ($cached = Cache::get($key)) {
            return $cached;
        }

        // expire cache at 6AM
        $expiresAt = Carbon::now()->endOfDay()->addSecond()->addHours(6);

        $service = brandService();
        $search = $service->newSearch();
        $search->setTagIds([ArticleType::TAG_ID_ARTICLE, $relateByTagId])
            ->setPageSize(5)
            ->setSort(Search::SORT_TYPE_READS_TOTAL_14_DAYS_DESC);
        $result = $service->run($search)->hydrate();

        Cache::put($key, $result, $expiresAt);

        return $result;
    }

    public static function fetchMostRead(int $tagId = null): Collection
    {
        if ($tagId) {
            $key = active_host() . '_most_read_articles_for_' . $tagId;
        } else {
            $key = active_host() . '_most_read_articles';
        }

        if ($cached = Cache::get($key)) {
            return $cached;
        }

        $tagIds = array_filter([ArticleType::TAG_ID_ARTICLE, $tagId]);

        // expire cache at 6AM
        $expiresAt = Carbon::now()->endOfDay()->addSecond()->addHours(6);
        $publishedFrom = Carbon::now()->subMonth();

        $service = brandService();
        $search = $service->newSearch();
        $search->setTagIds($tagIds)
            ->setFromDate($publishedFrom)
            ->setPageSize(5)
            ->setSort(Search::SORT_TYPE_READS_TOTAL_WEEK_DESC);
        $result = $service->run($search)->hydrate();

        Cache::put($key, $result, $expiresAt);

        return $result;
    }
}
