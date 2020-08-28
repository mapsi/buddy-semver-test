<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\ContentApi\Entities\Article;
use App\Services\ContentApi\Search;
use App\Services\ContentApi\Service;
use App\Services\ContentApi\TagGroups\ArticleType;
use Exception;
use Illuminate\Http\Request;
use Laravelium\Sitemap\Sitemap;

class SitemapController extends Controller
{
    public function sitemap(Request $request, Sitemap $sitemap, Service $service)
    {
        $baseUrl = $request->root();
        $search = $service->newSearch();
        $search->setSort(Search::SORT_TYPE_LATEST);
        $search->setPageSize(500);

        // counters
        // $sitemapCounter = 0;

        foreach (range(1, 60) as $page) {
            $search->setStartPage($page);

            try {
                $items = $service->run($search)->hydrate();
            } catch (Exception $e) {
                report($e);

                $items = [];
            }

            if (count($items) === 0) {
                break;
            }

            foreach ($items as $item) {
                if ($url = $item->getCanonicalUrl()) {
                    $sitemap->add($baseUrl . $url, $item->getLastUpdateDate());
                }
            }

            // // you need to check for unused items
            // if (! empty($sitemap->model->getItems())) {
            //     // generate sitemap with last items
            //     $sitemap->store('xml', 'sitemap-' . $sitemapCounter);
            //     // add sitemap to sitemaps array
            //     $sitemap->addSitemap(secure_url('sitemap-' . $sitemapCounter . '.xml'));
            //     // reset items array
            //     $sitemap->model->resetItems();
            // }

            // $sitemapCounter++;
        }

        // generate new sitemapindex that will contain all generated sitemaps above
        // $sitemap->store('sitemapindex', 'sitemap');
        return $sitemap->render('xml');
    }

    public function googleNews(Sitemap $sitemap, Service $service)
    {
        $search = $service->newSearch();
        $search->setSort(Search::SORT_TYPE_LATEST);
        $search->setTagIds([Article::ENTITY_TYPE_TAG_ID]);
        $search->setPageSize(1000);
        $search->setFromDate(now()->subDays(2));

        $results = $service->run($search);

        foreach ($results->toArray()['items'] as $item) {
            $this->addEntry($sitemap, $item);
        }

        return $sitemap->render('google-news');
    }

    private function addEntry(Sitemap &$sitemap, array $entry): void
    {
        $siteName = get_host_config('title');

        if (! empty($entry['sourceLink'])) {
            $sitemap->add(
                $entry['sourceLink'],
                $entry['lastUpdated'],
                null,
                null,
                [],
                strip_tags($entry['title']),
                [],
                [],
                [
                    'sitename' => $siteName,
                    'language' => 'en',
                    'publication_date' => $entry['publishedFrom'],
                ],
            );
        }
    }
}
