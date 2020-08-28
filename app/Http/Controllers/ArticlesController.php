<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Services\ContentApi\Entities\Article;
use App\Services\ContentApi\Entities\Magazine;
use App\Services\ContentApi\Entities\Supplement;
use App\Services\ContentApi\Search;
use App\Services\ContentApi\Service;
use App\Services\ContentApi\TagGroups\ArticleCategory;
use App\Services\ContentApi\TagGroups\ArticleType;
use App\Services\ContentApi\TagGroups\MagazineSection;
use App\Services\ContentApi\TagGroups\Topic;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ArticlesController extends Controller
{
    public function index()    {
        $data = $this->cacheResponseData(function () {
            $service = $this->service;

            $siteLeadQuery = $service->newSearch()
                ->setTagIds([Article::ENTITY_TYPE_TAG_ID, ArticleCategory::TAG_ID_SITE_LEAD, ArticleCategory::TAG_ID_FRONT_PAGE_PROMOTED])
                ->excludeTagIds([ArticleType::TAG_ID_LEGAL_UPDATE])
                ->setPageSize(3);
            $siteLead = $service->run($siteLeadQuery)->hydrate();

            $featuredQuery = $service->newSearch()
                ->setTagIds([Article::ENTITY_TYPE_TAG_ID, ArticleCategory::TAG_ID_FEATURED])
                ->setPageSize(6);
            $featured = $service->run($featuredQuery)->exclude($siteLead)->hydrate()->take(3);

            $newsQuery = $service->newSearch()
                ->setTagIds([Article::ENTITY_TYPE_TAG_ID, ArticleType::TAG_ID_NEWS])
                ->excludeTagIds([ArticleType::TAG_ID_LEGAL_UPDATE])
                ->setPageSize(10);
            $news = $service->run($newsQuery)->exclude($siteLead, $featured)->hydrate()->take(4);

            $analysisQuery = $service->newSearch()
                ->setTagIds([Article::ENTITY_TYPE_TAG_ID, (active_host() === 'iam' ? ArticleType::TAG_ID_ANALYSIS : ArticleType::TAG_ID_ANALYSIS_MARKET)])
                ->excludeTagIds([ArticleType::TAG_ID_LEGAL_UPDATE])
                ->setPageSize(14);
            $analysis = $service->run($analysisQuery)->exclude($siteLead, $featured, $news)->hydrate()->take(4);

            $legalUpdateQuery = $service->newSearch()
                ->setTagIds([Article::ENTITY_TYPE_TAG_ID, ArticleType::TAG_ID_LEGAL_UPDATE])
                ->setPageSize(15);
            $legalUpdates = $service->run($legalUpdateQuery)->exclude($siteLead, $featured, $news)->hydrate()->take(5);

            $researchQuery = $service->newSearch()
                ->setTagIds([Article::ENTITY_TYPE_TAG_ID, ArticleType::TAG_ID_RESEARCH])
                ->setPageSize(19);
            $research = $service->run($researchQuery)->exclude($siteLead, $featured, $news, $legalUpdates, $analysis)->hydrate()->take(4);

            $insightQuery = $service->newSearch()
                ->setTagIds([Article::ENTITY_TYPE_TAG_ID, ArticleType::TAG_ID_INSIGHT])
                ->setPageSize(23);
            $insight = $service->run($insightQuery)->exclude($siteLead, $featured, $news, $legalUpdates, $analysis, $research)->hydrate()->take(4);

            $insightsQuery = $service->newSearch()
                ->setTagIds([Article::ENTITY_TYPE_TAG_ID, Magazine::MAGAZINE_SECTION_INSIGHTS])
                ->setPageSize(10);
            $insights = $service->run($insightsQuery)->exclude($siteLead, $featured)->hydrate()->take(4);

            $mostRead = Service::fetchMostRead();

            return compact('siteLead', 'featured', 'news', 'analysis', 'legalUpdates', 'research', 'insight', 'insights', 'mostRead');
        });

        return view('articles.index', $data);
    }

    public function topicsAndSectors(array $tag, bool $isTopic)
    {
        $data = $this->cacheResponseData(function () use ($tag, $isTopic) {
            // $this->service is null at this stage due to how the middlewares work
            // let's fix properly another day...
            $service = brandService();
            $tagId = $tag['tagId'];

            $siteLeadQuery = $service->newSearch()
                ->setTagIds([Article::ENTITY_TYPE_TAG_ID, ArticleCategory::TAG_ID_SITE_LEAD, $tagId])
                ->setPageSize(3);
            $siteLead = $service->run($siteLeadQuery)->hydrate();

            $latestQuery = $service->newSearch()
                ->setTagIds([Article::ENTITY_TYPE_TAG_ID, $tagId])
                ->setPageSize(7);
            $latest = $service->run($latestQuery)->exclude($siteLead)->hydrate()->take(4);

            $newsQuery = $service->newSearch()
                ->setTagIds([Article::ENTITY_TYPE_TAG_ID, ArticleType::TAG_ID_NEWS, $tagId])
                ->setPageSize(11);

            $news = $service->run($newsQuery)->exclude($siteLead, $latest)->hydrate()->take(4);

            $analysisQuery = $service->newSearch()
                ->setTagIds([Article::ENTITY_TYPE_TAG_ID, ArticleType::TAG_ID_ANALYSIS, $tagId])
                ->setPageSize(15);
            $analysis = $service->run($analysisQuery)->exclude($siteLead, $latest, $news)->hydrate()->take(4);

            $researchQuery = $service->newSearch()
                ->setTagIds([Article::ENTITY_TYPE_TAG_ID, ArticleType::TAG_ID_RESEARCH, $tagId])
                ->setPageSize(19);
            $research = $service->run($researchQuery)->exclude($siteLead, $latest, $news, $analysis)->hydrate()->take(4);

            $insightQuery = $service->newSearch()
                ->setTagIds([Article::ENTITY_TYPE_TAG_ID, ArticleType::TAG_ID_INSIGHT, $tagId])
                ->setPageSize(23);
            $insight = $service->run($insightQuery)->exclude($siteLead, $latest, $news, $analysis, $research)->hydrate()->take(4);

            $insightsQuery = $service->newSearch()
                ->setTagIds([Article::ENTITY_TYPE_TAG_ID, Magazine::MAGAZINE_SECTION_INSIGHTS, $tagId])
                ->setPageSize(27);
            $insights = $service->run($insightsQuery)->exclude($siteLead, $latest, $news, $analysis, $research)->hydrate()->take(4);

            $relateByTagId = $tag['tagId'];
            $trending = Service::fetchTrending($relateByTagId);

            $tagId = $tag['tagId'];

            $viewAllLinks = [
                'news' => "/search?tagIds={$tagId}," . ArticleType::TAG_ID_BLOG,
                'analysis' => "/search?tagIds={$tagId}," . ArticleType::TAG_ID_ANALYSIS,
                'research' => "/search?tagIds={$tagId}," . ArticleType::TAG_ID_RESEARCH,
                'insight' => "/search?tagIds={$tagId}," . ArticleType::TAG_ID_INSIGHT,
                'latest' => "/search?tagIds={$tagId}",
            ];

            $activeTopic = $tag;
            $topic = $tag;

            $showExpert = ($tag['typeId'] === Topic::TAG_GROUP_ID);

            return compact('activeTopic', 'topic', 'siteLead', 'latest', 'news', 'analysis', 'research', 'insight', 'insights', 'trending', 'viewAllLinks', 'showExpert');
        });

        return view('topics.show', $data);
    }

    public function news()
    {
        $data = $this->cacheResponseData(function () {
            $service = $this->service;

            $siteLeadQuery = $service->newSearch()
                ->setTagIds([Article::ENTITY_TYPE_TAG_ID, ArticleType::TAG_ID_BLOG, ArticleCategory::TAG_ID_SITE_LEAD])
                ->setPageSize(3);

            $latestQuery = $service->newSearch()
                ->setTagIds([Article::ENTITY_TYPE_TAG_ID, ArticleType::TAG_ID_BLOG])
                ->setPageSize(38);

            $siteLead = $service->run($siteLeadQuery)->hydrate();
            $latest = $service->run($latestQuery)->exclude($siteLead)->hydrate()->take(35);

            $relateByTagId = ArticleType::TAG_ID_NEWS;
            $trending = Service::fetchTrending($relateByTagId);

            $viewAllLinks = ['news' => '/search?tagIds=' . ArticleType::TAG_ID_BLOG];

            return compact('siteLead', 'latest', 'trending', 'viewAllLinks');
        });

        return view('articles.news', $data);
    }

    public function features()
    {
        return view('articles.features');
    }

    public function analysis(string $subject = null)
    {
        $data = $this->cacheResponseData(function () use ($subject) {
            $service = $this->service;

            $tagId = ArticleType::TAG_ID_ANALYSIS;
            $viewAllLinks = "/search?tagIds={$tagId}";
            $pageTitle = 'Analysis';

            $tag = null;
            if ($subject) {
                $tags = cacheGroupTags(ArticleType::class);
                if ($tag = $tags->firstWhere('slug', 'analysis-' . $subject)) {
                    $tagId = $tag['tagId'];
                    $viewAllLinks = "/search?tagIds={$tag['tagId']}";
                    $pageTitle = $tag['name'];
                }
            }

            $siteLeadSearch = $service->newSearch();
            $siteLeadSearch
                ->setTagIds([
                    [$tagId, ArticleCategory::TAG_ID_SITE_LEAD],
                    // [$tagId, ArticleCategory::TAG_ID_SITE_LEAD], // change this to featured when it's ready
                ])
                ->setPageSize(3);

            $siteLead = $service->run($siteLeadSearch)->hydrate();

            $latestSearch = $service->newSearch();
            $latestSearch
                ->setTagIds([$tagId])
                ->setPageSize(27 + 3);

            $latest = $service->run($latestSearch)->exclude($siteLead)->hydrate()->take(27);

            $relateByTagId = ArticleType::TAG_ID_ANALYSIS;
            $trending = Service::fetchTrending($relateByTagId);


            return compact('pageTitle', 'siteLead', 'latest', 'trending', 'viewAllLinks');
        });

        return view('articles.analysis', $data);
    }

    public function legalUpdate()
    {
        $data = $this->cacheResponseData(function () {
            $tagId = ArticleType::TAG_ID_LEGAL_UPDATE;
            $viewAllLinks = "/search?tagIds={$tagId}";

            $service = $this->service;

            // @see https://globelbr.atlassian.net/browse/GXR-1274
            $siteLead = collect([]);

            $latestSearch = $service->newSearch();
            $latestSearch
                ->setTagIds([$tagId])
                ->setPageSize(27 + 3);

            $latest = $service->run($latestSearch)->exclude($siteLead)->hydrate()->take(27);

            $relateByTagId = ArticleType::TAG_ID_LEGAL_UPDATE;
            $trending = Service::fetchTrending($relateByTagId);

            return compact('siteLead', 'latest', 'trending', 'viewAllLinks');
        });

        return view('articles.legal_update', $data);
    }

    public function research()
    {
        $data = $this->cacheResponseData(function () {
            $tagId = ArticleType::TAG_ID_RESEARCH;
            $viewAllLinks = "/search?tagIds={$tagId}";

            $service = $this->service;

            $siteLeadSearch = $service->newSearch();
            $siteLeadSearch
                ->setTagIds([$tagId])
                // ->setTagIds([$tagId, ArticleCategory::TAG_ID_SITE_LEAD])
                ->setPageSize(3);

            $siteLead = $service->run($siteLeadSearch)->hydrate();

            $latestSearch = $service->newSearch();
            if (active_host() === 'wtr') {
                $latestSearch
                    ->setTagIds([$tagId])
                    ->setPageSize(27 + 3);

                $latest = $service->run($latestSearch)->exclude($siteLead)->hydrate()->take(27);
            } else {
                $latestSearch
                    ->setTagIds([MagazineSection::TAG_ID_DATA_CENTRE])
                    ->setPageSize(35);

                $latest = $service->run($latestSearch)->exclude($siteLead)->hydrate();
            }

            $relateByTagId = ArticleType::TAG_ID_RESEARCH;
            $trending = Service::fetchTrending($relateByTagId);

            return compact('siteLead', 'latest', 'trending', 'viewAllLinks');
        });

        return view('articles.research', $data);
    }

    public function thoughtLeadership()
    {
        $data = $this->cacheResponseData(function () {
            $tagIds = ArticleType::TAG_ID_THOUGHT_LEADERSHIP;
            $service = $this->service;
            $query = $service->newSearch()->setTagIds([Supplement::ENTITY_TYPE_TAG_ID]);

            $magazines = $service->run($query)->hydrate();
            return compact('magazines', 'tagIds');
        });

        return view('articles.insight.thought-leadership', $data);
    }

    public function insight(Brand $brand, string $article_type)
    {
        $values = Cache::remember('insight_' . $brand->id . '_' . $article_type, 3 * 60, function () use ($article_type) {
            $article_type = str_replace('-', ' ', $article_type); // Replace hyphens with spaces

            $article_types = [
                'Insight' => 'insight',
            ];

            $topics = ArticleType::nameOneOf(array_keys($article_types))->pluck('id', 'name');

            $viewAllLinks = [];

            foreach ($topics as $topic_name => $id) {
                $viewAllLinks[$article_types[$topic_name]] = '/search?search=&order=date_desc&article_types[]=' . $id;
            }

            if (! in_array($article_type, ['interview'])) {
                abort(404);
            }

            $article_type = ArticleType::nameIs($article_type)->firstOrFail();

            $latest = $article_type->articles()->with('media')->latest('published_at')->since('10 years ago')->limit(27)->get();

            return [
                'article_type' => $article_type,
                'latest' => $latest,
                'viewAllLinks' => $viewAllLinks,
            ];
        });

        return view('articles.insight', $values);
    }

    public function interviews()
    {
        $data = $this->cacheResponseData(function () {
            $subject = 'Interview';

            $tagId = ArticleType::TAG_ID_INTERVIEW;
            $viewAllLinks = "/search?tagIds={$tagId}";

            $service = $this->service;

            $siteLeadSearch = $service->newSearch();
            $siteLeadSearch
                ->setTagIds([$tagId, ArticleCategory::TAG_ID_SITE_LEAD])
                ->setPageSize(3);

            $siteLead = $service->run($siteLeadSearch)->hydrate();

            $latestSearch = $service->newSearch();
            $latestSearch
                ->setTagIds([$tagId])
                ->setPageSize(27 + 3);

            $latest = $service->run($latestSearch)->exclude($siteLead)->hydrate()->take(27);

            $relateByTagId = ArticleType::TAG_ID_INTERVIEW;
            $trending = Service::fetchTrending($relateByTagId);

            return compact('subject', 'siteLead', 'latest', 'trending', 'viewAllLinks');
        });

        return view('articles.interviews', $data);
    }

    public function infographics()
    {
        $data = $this->cacheResponseData(function () {
            $service = $this->service;

            $query = $service->newSearch()
                ->setTagIds([Article::ENTITY_TYPE_TAG_ID, ArticleType::TAG_ID_INFOGRAPHIC])
                ->setSort(Search::SORT_TYPE_LATEST)
                ->setPageSize(30);
            $latest = $service->run($query)->hydrate();
            $remaining = $latest->splice(3);

            $viewAllLinks = ['infographic' => '/search?tagIds=' . ArticleType::TAG_ID_INFOGRAPHIC];

            return compact('latest', 'remaining', 'viewAllLinks');
        });

        return view('articles.infographics', $data);
    }

    public function media(string $articleTypeParam)
    {
        $articleTypesMap = [
            'webinars' => [
                'article_type' => 'webinar',
                'title' => 'Webinars',
            ],
        ];

        if (! isset($articleTypesMap[$articleTypeParam])) {
            abort(404);
        }

        $data = $this->cacheResponseData(function () use ($articleTypeParam, $articleTypesMap) {
            $articleType = $articleTypesMap[$articleTypeParam]['article_type'];

            $search = $this->service->newSearch();
            $search
                ->setTagIds([ArticleType::TAG_ID_WEBINAR])
                ->setPageSize(30)
                ->setSort(Search::SORT_TYPE_LATEST)
                ->withContent();
            $webinars = $this->service->run($search)->hydrate();

            $remaining = $webinars->splice(3);

            return [
                'article_type' => $articleType,
                'latest' => $webinars,
                'remaining' => $remaining,
                'page_title' => $articleTypesMap[$articleTypeParam]['title'],
            ];
        });

        return view('articles.media', $data);
    }

    public function download(string $articleID)
    {
        $articlePdfs = include 'articlepdfs.php';

        $article = $this->service->getContent($articleID);

        array_key_exists($article->getOriginalId(), $articlePdfs) ?? abort(404);

        $path = $articlePdfs[$article->getOriginalId()];
        $filename = Str::slug($article->getTitle()) . '.pdf';

        return response()->streamDownload(function () use ($path) {
            echo file_get_contents($path);
        }, $filename);
    }
}
