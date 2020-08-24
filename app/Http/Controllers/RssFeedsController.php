<?php

namespace App\Http\Controllers;

use App;
use App\Models\Brand;
use App\Services\ContentApi\Entities\Article;
use App\Services\ContentApi\TagGroups\ArticleType;
use App\Services\ContentApi\TagGroups\Topic;
use URL;

class RssFeedsController extends Controller
{
    public function articles(Brand $brand)
    {
        // create new feed
        $feed = App::make("feed");

        // multiple feeds are supported
        // if you are using caching you should set different cache keys for your feeds

        $ttl = 60;
        $cacheKey = 'rss-' . $brand->id . '-article';
        // cache the feed for 60 minutes (second parameter is optional)
        $feed->setCache($ttl, $cacheKey);

        // check if there is cached feed and build new only if is not
        if (! $feed->isCached()) {
            // creating rss feed with our most recent 50 posts
            $service = brandService();
            $search = $service->newSearch();
            $search
                ->setTagIds([Article::ENTITY_TYPE_TAG_ID])
                ->setPageSize(100);

            $articles = $service->run($search, 1)->hydrate();

            // set your feed's title, description, link, pubdate and language
            $feed->title = $brand->title;
            if ($brand->name == 'WTR') {
                $feed->description = 'The latest content from the ' . $brand->name . ' Magazine';
            } else {
                $feed->description = 'The latest content from ' . $brand->name;
            }
            //$feed->logo = 'http://yoursite.tld/logo.jpg';
            $feed->link = url()->route('rss.articles');
            $feed->logo = url("/images/logos/" . $brand->machine_name . "-logo.png");
            $feed->setDateFormat('carbon'); // 'datetime', 'timestamp' or 'carbon'
            $feed->pubdate = $articles->first()->getPublicationDate(false);
            $feed->lang = 'en';
            $feed->setShortening(true); // true or false
            $feed->setTextLimit(400); // maximum length of description text

            foreach ($articles as $article) {
                $categories = [] + $article->getTagGroup(ArticleType::class)->map(function ($item) {
                    return $item->getName();
                })->toArray()
                    + $article->getTagGroup(Topic::class)->map(function ($item) {
                        return $item->getName();
                    })->toArray();
                // set item's $title, $author, $link, $pubdate, $description, $content='', $enclosure = [], $category='', $subtitle='', $duration =''


                $feed->add($article->getTitle(), $article->getAuthors()->map(function ($author) {
                    return $author->getName();
                })->implode(','), URL::to($article->getCanonicalUrl()), $feed->formatDate($article->getPublicationDate(false), 'rss'), $article->getPrecis(), '', [], $categories);
            }
        }

        return $feed->render('rss', $ttl, $cacheKey);
    }
}
