<?php

namespace App\Http\ViewComponents;

use App\Models\Article;
use App\Http\ViewComponents\Exceptions\PageBlockWithEmptySource;
use App\Models\Brand;

class HeroImageComponent extends PageBlockBase
{
    const SLUG = 'hero_image';

    /**
     * @return array
     * @throws Exceptions\PageBlockSkipRender
     * @throws PageBlockWithEmptySource
     */
    public function loadData()
    {
        $this->shouldRender();
        $siteLead = Article::promotedHomepageFirst()->with('media')->latest('published_at')->notOfType('legal update')->isSiteLead()->limit(3)->get();

        if ($siteLead->isEmpty()) {
            throw new PageBlockWithEmptySource();
        }

        return [
            'article' => $siteLead->first(),
            'brand' => Brand::whereMachineName('iam')->first(),
        ];
    }

    /**
     * @return string
     */
    public function view()
    {
        return 'blocks.hero_image';
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
