<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ContentApi\Entities\QATopic;
use App\Services\ContentApi\TagGroups\Brand;
use App\Services\ContentApi\Entities\Question;
use App\Services\ContentApi\Entities\Article;
use App\Services\ContentApi\Search;
use App\Services\ContentApi\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use App\Models\User;

class KnowHowController extends Controller
{

    public function index(Request $request, Service $service)
    {
        $search = $service->newSearch();
        $search->setTagIds([QATopic::ENTITY_TYPE_TAG_ID, Brand::TAG_ID_KNOWHOWGCR]);
        $search->setSort(Search::SORT_TYPE_TITLE);
        $topics = $service->run($search)->hydrate(null, QATopic::class);
        return view('series.insight.know-how.index', compact('topics'));
    }

    public function show(Request $request, Service $service)
    {
        $entity = $this->getKnowHowTopic($request, $service);
        $topicLayout = $entity->getTopicLayout();
        $articles = $entity->fetchArticles();
        $jurisdictions = $entity->getJurisdictions();
        return view('series.insight.know-how.show', compact('entity', 'jurisdictions', 'topicLayout', 'articles'));
    }

    public function showReport(Request $request, Service $service)
    {
        $path = $request->path();
        $path = str_replace('/report', '', $path);
        $entity = $service->getContentBySourceLink($path, QATopic::class);

        $allJurisdictions = $entity->getJurisdictions();
        $this->validate($request, [
            'j' => 'required|array',
            'j.*' => 'integer|in:' . $allJurisdictions->keys()->implode(','),
            's-t' => 'array',
            's-t.*' => 'array',
            's-t.*.qs.*' => 'integer'
        ]);
        $jurisdictions = $request->get('j');
        $subTopics = $request->get('s-t');
        $jurisdictions = $allJurisdictions->filter(function ($jurisdiction, $key) use ($jurisdictions) {

            return in_array($key, $jurisdictions);
        });
        if ($jurisdictions->count() == 1 && empty($subTopics)) {
            return redirect()->route('know-how.show-jurisdiction-report', [
                    'topic' => $entity->getSlug(),
                    'country' => Str::slug($jurisdictions->first()->getCountry())
                ]);
        }

        $topicLayout = $entity->getTopicLayout();
        if (! empty($subTopics)) {
            $topicLayout = $this->getReportLayout($topicLayout, $subTopics);
        }

        return view('series.insight.know-how.report', compact('entity', 'topicLayout', 'jurisdictions', 'allJurisdictions'));
    }

    private function getKnowHowTopic(Request $request, Service $service): QATopic
    {
        $path = $request->path();
        $entity = $service->getContentBySourceLink($path, QATopic::class);
        return $entity;
    }

    /**
     * It filters sub-topics and questions according to the data submitted
     * @param  Illuminate\Support\Collection $layout original topic layout
     * @param  array $subTopics data submitted through the form
     * @return Illuminate\Support\Collection report topic layout
     */
    private function getReportLayout(Collection $layout, array $subTopics): Collection
    {
        return $layout->filter(function ($subTopic, $key) use ($subTopics) {

            return Arr::has($subTopics, $key);
        })->each(function ($subTopic, $key) use ($subTopics) {

            $submittedQuestions = $subTopics[$key]['qs'];
            $questions = $subTopic['questions']->filter(function ($question, $key) use ($submittedQuestions) {

                return in_array($key, $submittedQuestions);
            });
            $subTopic->put('questions', $questions);
        });
    }

    public function showJurisdictionReport(Request $request, Service $service)
    {
        $jurisdiction = ucwords(str_replace('-', ' ', $request->segment(5)));

        $path = $request->path();
        $path = str_replace('/report/' . $request->segment(5), '', $path);
        $entity = $service->getContentBySourceLink($path, QATopic::class);

        $topicLayout = $entity->getTopicLayout();
        $allJurisdictions = $entity->getJurisdictions();
        $jurisdictions = $allJurisdictions->filter(function ($topicJurisdiction) use ($jurisdiction) {
            return strtolower($topicJurisdiction->getCountry()) === strtolower($jurisdiction);
        });

        return view('series.insight.know-how.report', compact('entity', 'topicLayout', 'jurisdictions', 'allJurisdictions'));
    }

    public function showArticle(Request $request, Service $service)
    {
        $path = $request->path();
        $entity = $service->getContentBySourceLink($path, Article::class);
        $topic = $entity->getRelations(QATopic::class)->first();
        $canView = $entity->canView(auth()->user() ?? new User());
        return view('editions.edition_show_article', compact('entity', 'topic', 'canView'));
    }
}
