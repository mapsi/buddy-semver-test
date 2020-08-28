<?php

namespace App\Http\ViewComponents;

use App\Http\ViewComponents\Exceptions\PageBlockWithEmptySource;
use App\Models\Article;

class NewsComponent extends PageBlockBase
{
    const SLUG = 'news';

    /**
     * @return array
     * @throws Exceptions\PageBlockSkipRender
     * @throws Exceptions\PageBlockWithEmptySource
     */
    public function loadData()
    {
        $this->shouldRender();

        $site_lead = Article::promotedHomepageFirst()->with('media')->latest('published_at')->notOfType('legal update')->isSiteLead()->limit(3)->get();
        $featured = Article::latest('published_at')->isFeatured()->exclude($site_lead)->limit(3)->get();
        $news = Article::latest('published_at')->ofType('news')->notOfType('legal update')->exclude($site_lead, $featured)->limit(4)->get();

        if ($news->isEmpty()) {
            throw new PageBlockWithEmptySource();
        }

        return compact('news');
    }

    /**
     * @return string
     */
    public function view()
    {
        return 'blocks.news';
    }

    /**
     * @return array
     * @throws Exceptions\PageBlockSkipRender
     * @throws PageBlockWithEmptySource
     */
    public function viewData()
    {
        return [
            'data' => $this->loadData(),
            'attributes' => $this->layout->getAttributesFromBlock(static::SLUG),
        ];
    }
}
