<?php

namespace App\Http\Controllers;

use App\Http\Resources\Articles;
use App\Models\ArticleType as ModelsArticleType;
use App\Models\Region;
use App\Models\Topic as ModelsTopic;
use App\Services\ContentApi\Entities\Article;
use App\Services\ContentApi\Entities\Profiles\AuthorProfile;
use App\Services\ContentApi\Entities\Profiles\FirmProfile;
use App\Services\ContentApi\Entities\Profiles\OrganisationProfile;
use App\Services\ContentApi\Entities\Profiles\PersonProfile;
use App\Services\ContentApi\Search;
use App\Services\ContentApi\Service;
use App\Services\ContentApi\TagGroups\ArticleType;
use App\Services\ContentApi\TagGroups\Country;
use App\Services\ContentApi\TagGroups\Topic;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $tags = [];

        $articleTypeIds = $request->get('article_types', []);
        if ($articleTypeIds && is_array($articleTypeIds)) {
            $tags = array_merge($tags, $this->extractTagFromEntity($articleTypeIds, ArticleType::class));
        }

        $topicIds = $request->get('topics', []);
        if ($topicIds && is_array($topicIds)) {
            $tags = array_merge($tags, $this->extractTagFromEntity($topicIds, Topic::class));
        }

        $regionsIds = $request->get('regions', []);
        if ($regionsIds && is_array($regionsIds)) {
            $tags = array_merge($tags, $this->extractTagFromEntity($regionsIds, Country::class));
        }

        $tagIds = $request->get('tagIds');
        foreach (explode(',', $tagIds) as $tagId) {
            if ($tag = Service::getGroupTagsByTagId($tagId)) {
                $tags[] = $tag;
            }
        }

        $sort = (! empty($request->get('search'))) ? Search::SORT_TYPE_RELEVANCE : Search::SORT_TYPE_LATEST;

        return view('search')
            ->with('layoutFile', get_host_config('search.layout'))
            ->with('search', $request->get('search', '') ?? '')
            ->with('tags', $tags)
            ->with('sort', $request->get('sort', $sort))
            ->with('page', $request->get('page', 1));
    }

    public function search(Request $request)
    {
        $service = $this->service;
        $search = $service->newSearch();

        $search->setQuery($request->get('search', '') ?? '');
        $sort = $request->get('sort', Search::SORT_TYPE_LATEST);
        if (is_numeric($sort)) {
            $search->setSort((int) $sort);
        }

        if ($request->get('sort') == Search::SORT_TYPE_RELEVANCE) {
            $search = $this->addSearchBoosts($search);
        }

        $search->setStartPage((int) $request->get('page', 1));
        $search->setPageSize(15);

        $defaultTagIds = [
            [Article::ENTITY_TYPE_TAG_ID],
            [AuthorProfile::PROFILE_TYPE_TAG_ID],
            [OrganisationProfile::PROFILE_TYPE_TAG_ID],
            [PersonProfile::PROFILE_TYPE_TAG_ID],
            [FirmProfile::PROFILE_TYPE_TAG_ID],
        ];

        $searchConfig = get_host_config('search.tags', []);
        if (count($searchConfig) > 0) {
            $defaultTagIds = $searchConfig;
        }

        $tagIds = $request->get('tagIds', null);
        $search->setTagIds($tagIds ? array_map(function ($defaultTagIdsGroup) use ($tagIds) {
            return array_map(function ($tagGroup) {
                return intval($tagGroup);
            }, array_merge($defaultTagIdsGroup, explode(',', $tagIds)));
        }, $defaultTagIds) : $defaultTagIds);
        $search->withContent();
        $search->withFacets();
        $search->pruneFacets();

        $results = $service->run($search);

        return (new Articles($results->paginate()))->additional([
            'facetGroups' => $results->facetGroups(),
        ]);
    }

    private function extractTagFromEntity(array $entityIds, string $tagGroup)
    {
        $map = [
            ArticleType::class => ModelsArticleType::class,
            Topic::class => ModelsTopic::class,
            Country::class => Region::class,
        ];

        $contentApiTags = cacheGroupTags($tagGroup);

        $tags = [];

        foreach ($entityIds as $entityId) {
            $class = $map[$tagGroup];

            if (! $model = $class::find($entityId)) {
                continue;
            }

            if (! $tag = $contentApiTags->firstWhere('name', $model->name)) {
                continue;
            }

            $tags[] = $tag;
        }

        return $tags;
    }

    private function addSearchBoosts(Search $search): Search
    {
        $search->setSearchBoosts(config('config.search_boost_parameters'));

        return $search;
    }
}
