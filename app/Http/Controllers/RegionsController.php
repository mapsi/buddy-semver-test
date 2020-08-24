<?php

namespace App\Http\Controllers;

use App\Models\Region;
use App\Services\ContentApi\Entities\Article;
use App\Services\ContentApi\TagGroups\ArticleCategory;
use App\Services\ContentApi\TagGroups\Country;
use App\Services\ContentApi\TagGroups\Topic;
use Illuminate\Support\Str;
use App\Services\ContentApi\Service;

class RegionsController extends Controller
{
    public function show($region, $subregion = null)
    {
        if ($subregion) {
            return redirect("/regions/{$subregion}");
        }

        $data = $this->cacheResponseData(function () use ($region) {
            $service = $this->service;
            $tags = cacheGroupTags(Country::class);
            $activeRegion = $tags->firstWhere('slug', Str::slug($region));

            if (is_null($activeRegion)) {
                abort(404);
            }

            $siteLeadSearch = $service->newSearch();
            $siteLeadSearch
                ->setTagIds([$activeRegion['tagId'], ArticleCategory::TAG_ID_SITE_LEAD])
                ->setPageSize(3);

            $siteLead = $service->run($siteLeadSearch)->hydrate();

            $latestSearch = $service->newSearch();
            $latestSearch
                ->setTagIds([$activeRegion['tagId']])
                ->setPageSize(7);

            $latest = $service->run($latestSearch)->exclude($siteLead)->hydrate()->take(4);

            $topics = [];

            $search = $service->newSearch();
            $search->setPageSize(10);

            // breaking these down as content is tagged with tags...
            $topicIds = [
                'wtr' => [
                    Topic::TAG_ID_ANTI_COUNTERFEITING,
                    Topic::TAG_ID_BRAND_MANAGEMENT,
                    Topic::TAG_ID_ENFORCEMENT_AND_LITIGATION,
                    Topic::TAG_ID_GOVERNMENT_POLICY,
                    Topic::TAG_ID_LAW_FIRMS,
                    Topic::TAG_ID_IP_OFFICES,
                    Topic::TAG_ID_ONLINE,
                    Topic::TAG_ID_PORTFOLIO_MANAGEMENT,
                    Topic::TAG_ID_TRADEMARK_LAW,
                ],
                'iam' => [
                    Topic::TAG_ID_DESIGNS,
                    Topic::TAG_ID_FINANCE,
                    Topic::TAG_ID_FRAND_SEPS,
                    Topic::TAG_ID_LAW_AND_POLICY,
                    Topic::TAG_ID_LITIGATION,
                    Topic::TAG_ID_MARKET_DEVELOPMENT,
                    Topic::TAG_ID_NON_PRACTICING_ENTITIES,
                    Topic::TAG_ID_PATENT_POOLS,
                    Topic::TAG_ID_PATENTS_LAW,
                    Topic::TAG_ID_STRATEGY,
                    Topic::TAG_ID_TECHNOLOGY_LICENCING,
                    Topic::TAG_ID_TRADE_SECRETS,
                    Topic::TAG_ID_VALUATION,
                ],
            ];

            $topicsGroup = cacheGroupTags(Topic::class);
            $previousTopics = collect();
            $limit = 11;

            foreach ($topicIds[active_host()] as $topicId) {
                $topicSearch = clone $search;
                $topicSearch->setTagIds([Article::ENTITY_TYPE_TAG_ID, $activeRegion['tagId'], $topicId])->setPageSize($limit);
                if (active_host() === 'wtr') {
                    $limit += 4;
                }

                $topicResults = $service->run($topicSearch)->exclude($siteLead, $latest, $previousTopics)->hydrate()->take(4);

                if (active_host() === 'wtr') {
                    $previousTopics = $previousTopics->concat($topicResults);
                }

                if ($topicResults->count()) {
                    $topicTag = $topicsGroup->firstWhere('tagId', $topicId);
                    $topics[] = [
                        'title' => Topic::$topics[$topicId],
                        'results' => $topicResults,
                        'viewAll' => "/search?tagIds={$topicTag['tagId']},{$activeRegion['tagId']}",
                    ];
                }
            }

            $viewAllLinks['latest'] = "/search?tagIds={$activeRegion['tagId']}";

            $relateByTagId = $activeRegion['tagId'];
            $trending = Service::fetchTrending($relateByTagId);

            $region = $activeRegion;

            return compact('activeRegion', 'region', 'siteLead', 'trending', 'latest', 'topics', 'viewAllLinks');
        });

        return view('regions.show', $data);
    }
}
