<?php

namespace App\Http\ViewComponents;

use App\Models\Article;
use App\Http\ViewComponents\Exceptions\PageBlockWithEmptySource;

class InsightsComponent extends PageBlockBase
{
    const SLUG = 'insights';

    /**
     * @return array
     * @throws Exceptions\PageBlockSkipRender
     * @throws PageBlockWithEmptySource
     */
    public function loadData()
    {
        $this->shouldRender();

        $site_lead = Article::promotedHomepageFirst()->with('media')->latest('published_at')->notOfType('legal update')->isSiteLead()->limit(3)->get();
        $featured = Article::latest('published_at')->isFeatured()->exclude($site_lead)->limit(3)->get();
        $insights = Article::latest('published_at')->magazineSectionIs('insights')->since('18 months ago')->exclude($site_lead, $featured)->limit(4)->get();

        if ($insights->isEmpty()) {
            throw new PageBlockWithEmptySource();
        }

        return compact('insights');
    }

    /**
     * @return string
     */
    public function view()
    {
        return 'blocks.insights';
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
