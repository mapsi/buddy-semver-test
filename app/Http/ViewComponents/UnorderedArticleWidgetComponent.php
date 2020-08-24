<?php

namespace App\Http\ViewComponents;

class UnorderedArticleWidgetComponent extends PageBlockBase
{
    /**
     * @return string
     */
    public function view()
    {
        return $this->attributes['template'] ?? 'blocks.unordered_article_widget';
    }

    /**
     * @return array
     * @throws Exceptions\PageBlockSkipRender
     * @throws PageBlockWithEmptySource
     */
    public function viewData()
    {
        return [
            'data' => [
                'articles' => $this->loadData()->hydrate(),
            ],
            'attributes' => $this->attributes,
        ];
    }
}
