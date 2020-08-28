<?php

namespace App\Http\ViewComponents;

use App\Models\Article;
use App\Http\ViewComponents\Exceptions\PageBlockWithEmptySource;

class TeasersComponent extends PageBlockBase
{
    const SLUG = 'teasers';

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
            'articles' => $siteLead->slice(1, 2),
        ];
    }

    /**
     * @return string
     */
    public function view()
    {
        return 'blocks.teasers';
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
