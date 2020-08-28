<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ContentApi\Entities\Article;
use App\Services\ContentApi\Entities\Report;
use Illuminate\Http\Request;

class ReportsController extends Controller
{
    public function index()
    {
        $data = $this->cacheResponseData(function () {
            $service = $this->service;

            $search = $service->newSearch();
            $search
                ->setTagIds([Report::ENTITY_TYPE_TAG_ID])
                ->withContent();

            return  [
                'reports' => $service->run($search, 10)->hydrate()
            ];
        });

        return view('reports.index', $data);
    }

    public function article(Request $request)
    {
        $service = $this->service;

        $segments = $request->segments();

        $reportUrl = implode('/', [$segments[0], $segments[1]]);
        $entity = null;

        $report = $service->getContentBySourceLink($reportUrl);
        $articles = $report->fetchArticles()->sortBy(function (Article $article) {
            return $article->getInfo('ReportOrder');
        });

        $dfpSectionName = 'Reports';
        $entity = new Article(array_merge($report->getData(), ['headline' => 'Predictions']));

        $articles = $articles->prepend($entity);

        if (count($segments) > 2) {
            $dfpSectionName = 'Content';
            $entity = $service->getContentBySourceLink(implode('/', $segments));
        }

        $user = auth()->user() ?? new User();
        $canView = $entity->canView($user);

        return view('reports.article', compact('report', 'articles', 'entity', 'canView'));
    }
}
