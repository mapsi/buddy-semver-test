<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ContentApi\Service;
use App\Services\ContentApi\Entities\Series;
use App\Services\ContentApi\Entities\Edition;
use App\Services\ContentApi\Search;
use App\Services\ContentApi\Entities\QATopic;
use App\Services\ContentApi\TagGroups\Brand;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SeriesController extends Controller
{
    private $seriesTags = [
        'guides' =>
        [
            'title' => 'Guides',
            'tag' => Series::TYPE_GUIDE_TAG_ID,
        ],
        'handbooks' =>
        [
            'title' => 'Handbooks',
            'tag' => Series::TYPE_INSIGHT_TAG_ID, //handbooks have type insight and template Handbook
        ],
        'reviews' =>
        [
            'title' => 'Regional Reviews',
            'tag' => Series::TYPE_REVIEW_TAG_ID
        ]
    ];

    public function index(Request $request, Service $service)
    {
        $group = ucfirst($request->segment(1));
        $typeName = $request->segment(2);

        $allLayouts = true;
        $currentTypeSeries = $this->getSeries($typeName, $service, $allLayouts);
        if ($currentTypeSeries->isEmpty()) {
            abort(404);
        }

        $allSeriesIds = $currentTypeSeries->map->getId()->values()->toArray();
        $editions = $this->getCurrentEditions($allSeriesIds, $service);
        if ($editions->isEmpty()) {
            abort(404);
        }

        $allSeries = $this->getAllSeries($service);
        $knowHows = $this->getKnowHows($service);
        $seriesType = $this->seriesTags[$typeName]['title'];

        return view('series.series-type.index', compact('group', 'seriesType', 'editions', 'knowHows', 'allSeries'));
    }

    public function show(Request $request, Service $service)
    {
        $path = $request->path();

        $this->validate($request, [
            'page' => 'integer|gt:0'
        ]);

        $entity = $service->getContentBySourceLink($path);

        $page = (int) $request->get('page', 1);

        $search = $service->newSearch();
        $search->setTagIds([Edition::ENTITY_TYPE_TAG_ID]);
        $search->setRelationIds([$entity->getId()]);
        $search->setSort(Search::SORT_TYPE_LATEST);
        $search->setStartPage($page);
        $search->setPageSize(10);
        $paginatedEditions = $service->run($search)->paginate();

        //TODO: to remove the additional call it would be possible to retrieve all results and do the pagination with Javascript/Vue
        $currentEdition = $this->getCurrentEdition($entity, $service);
        $currentEditionId = $currentEdition->getId();

        $editions = $paginatedEditions->getCollection()->filter(function ($edition) use ($currentEditionId) {
            return $edition->getId() != $currentEditionId;
        });

        $paginatedEditions->setCollection($editions);

        if ($entity->getTemplate() == 'Handbook') {
            $seriesType = 'handbooks';
        } else {
            $seriesType = Str::plural($entity->getType()->getSlug());
        }

        $currentTypeSeries = $this->getSeries($seriesType, $service);
        $typeSeriesData = $this->createTypeSeriesData($seriesType, $currentTypeSeries);

        return view('series.show', compact('entity', 'currentEdition', 'paginatedEditions', 'seriesType', 'typeSeriesData'));
    }

    private function getCurrentEdition(Series $series, Service $service)
    {
        $search = $service->newSearch();
        $search->setTagIds([Edition::ENTITY_TYPE_TAG_ID]);
        $search->setRelationIds([$series->getId()]);
        $search->setSort(Search::SORT_TYPE_LATEST);
        $allEditions = $service->run($search)->hydrate(null, Edition::class);
        return $allEditions->filter->isCurrent()->first() ?? $allEditions->first();
    }

    private function getCurrentEditions(array $allSeriesIds, Service $service): Collection
    {
        $search = $service->newSearch();
        $search->setOrRelations($allSeriesIds);
        $search->setSort(Search::SORT_TYPE_LATEST);
        $editions = $service->run($search)->hydrate(null, Edition::class);

        $editions = $editions->filter->isCurrent();

        return $editions;
    }

    private function getSeries(string $seriesType, Service $service, bool $allLayouts = false): Collection
    {
        if (! isset($this->seriesTags[$seriesType])) {
            return collect();
        }

        $seriesTag = $this->seriesTags[$seriesType];

        $search = $service->newSearch();
        $search->setTagIds([$seriesTag['tag']]);
        $search->setSort(Search::SORT_TYPE_LATEST);
        $allSeries = $service->run($search)->hydrate(null, Series::class);

        $allSeries = $this->getSeriesWithEditions($allSeries, $service);

        if (! $allLayouts) {
            $allSeries = $allSeries->filter->isParallel();
        }

        if ($seriesType == 'handbooks') {
            $allSeries = $allSeries->filter(function ($series) {
                return $series->getTemplate() == 'Handbook';
            });
        }
        return $allSeries;
    }

    /**
     * Retrieve all the editions and check if each series has at leas one of them
     * @param  Collection $allSeries list of all series
     * @param  Service    $service
     * @return Collection series which have at least one editions
     */
    private function getSeriesWithEditions(Collection $allSeries, Service $service): Collection
    {
        $allSeriesIds = $allSeries->map->getId()->values()->toArray();

        $search = $service->newSearch();
        $search->setOrRelations($allSeriesIds);
        $search->setSort(Search::SORT_TYPE_LATEST);
        $editions = $service->run($search)->hydrate(null, Edition::class);

        return $allSeries->filter(function ($series) use ($editions) {
            return $editions->filter(function ($edition) use ($series) {
                return $edition->getSeries()->getId() == $series->getId();
            })->count() >= 1;
        });
    }

    private function getKnowHows(Service $service): Collection
    {
        $search = $service->newSearch();
        $search->setTagIds([QATopic::ENTITY_TYPE_TAG_ID, Brand::TAG_ID_KNOWHOWGCR]);
        $search->setSort(Search::SORT_TYPE_TITLE);
        return $service->run($search)->hydrate(null, QATopic::class);
    }

    private function getAllSeries(Service $service): Collection
    {
        $allSeries = collect();

        foreach ($this->seriesTags as $type => $seriesType) {
            $typeSeries = $this->getSeries($type, $service);
            if ($typeSeries->isEmpty()) {
                continue;
            }

            $typeSeries = $this->createTypeSeriesData($type, $typeSeries);
            $allSeries->push($typeSeries);
        }

        return $allSeries;
    }

    public function createTypeSeriesData(string $type, Collection $series): Collection
    {
        $typeSeries = collect();
        $typeSeries->put('title', $this->seriesTags[$type]['title']);
        $typeSeries->put('series', $series);

        return $typeSeries;
    }
}
