<?php

namespace App\Http\ViewComponents;

use App\Http\ViewComponents\Exceptions\PageBlockWithEmptySource;

class PagedArticleListComponent extends PageBlockBase
{
    public function view()
    {
        return 'blocks.paged_article_list';
    }

    /**
     * @return array
     * @throws Exceptions\PageBlockSkipRender
     * @throws PageBlockWithEmptySource
     */
    public function viewData()
    {
        return [
            'articles' => $this->loadData()->paginate(),
            'attributes' => $this->attributes,
        ];
    }
}
