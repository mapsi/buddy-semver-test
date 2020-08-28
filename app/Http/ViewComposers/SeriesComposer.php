<?php

namespace App\Http\ViewComposers;

use App\Services\ContentApi\Entities\Edition;
use App\Services\ContentApi\Entities\Series;
use App\Services\ContentApi\Results;
use App\Services\ContentApi\Search;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class SeriesComposer
{
    const ACTIVE_SURVEYS_TTL = 1;
    const ACTIVE_SURVEYS_CACHE_KEY = 'navigation_active_surveys';
    const ACTIVE_INSIGHTS_TTL = 1;
    const ACTIVE_INSIGHTS_CACHE_KEY = 'navigation_active_insights';
    const INSIGHT_EDITION_ID = 'CB250EAC007BA77AE38175D4EB831C2E61A44A80';
    const INSIGHT_SERIES_ID = '7DE7F0392E88F0845AC648F3EF6D861BF018FC55';

    /**
     * @param View $view
     */
    public function compose(View $view)
    {
        $view->with('activeSurveys', $this->getActiveSurveys());
        $view->with('activeInsights', $this->getActiveInsights());
    }

    /**
     * @return Collection
     */
    private function getActiveSurveys()
    {
        $service = brandService();

        $search = $service->newSearch();
        $search->setTagIds([Edition::ENTITY_TYPE_TAG_ID]);
        $search->excludeTagIds([Series::TYPE_INSIGHT_TAG_ID]);
        $search->setSort(Search::SORT_TYPE_TITLE);

        $results = ['items' => Arr::where($service->run($search, self::ACTIVE_SURVEYS_TTL)->toArray()['items'], function ($item) {
            return ! Str::contains(strtolower($item['title']), strtolower('handbook'));
        })];

        return (new Results($results))->hydrate();
    }

    /**
     * @return Collection
     */
    private function getActiveInsights()
    {
        $service = brandService();

        $search = $service->newSearch();
        $search->setRelationIds([self::INSIGHT_SERIES_ID]);

        return $service->run($search, self::ACTIVE_SURVEYS_TTL)->hydrate();
    }
}
