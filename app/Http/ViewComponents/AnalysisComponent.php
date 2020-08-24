<?php

namespace App\Http\ViewComponents;

use App\Http\ViewComponents\Exceptions\PageBlockWithEmptySource;
use App\Models\Article;

class AnalysisComponent extends PageBlockBase
{
    const SLUG = 'analysis';

    /**
     * @return array
     * @throws Exceptions\PageBlockSkipRender
     * @throws Exceptions\PageBlockWithEmptySource
     */
    public function loadData()
    {
        $this->shouldRender();

        $siteLead = Article::promotedHomepageFirst()->with('media')->latest('published_at')->notOfType('legal update')->isSiteLead()->limit(3)->get();
        $featured = Article::latest('published_at')->isFeatured()->exclude($siteLead)->limit(3)->get();
        $news = Article::latest('published_at')->ofType('news')->notOfType('legal update')->exclude($siteLead, $featured)->limit(4)->get();
        $analysis = Article::latest('published_at')->with('media')->ofType('analysis')->notOfType('legal update')->exclude($siteLead, $featured, $news)->limit(4)->get();

        if ($analysis->isEmpty()) {
            throw new PageBlockWithEmptySource();
        }

        return compact('analysis');
    }

    /**
     * @return string
     */
    public function view()
    {
        return 'blocks.analysis';
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
