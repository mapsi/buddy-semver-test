<?php

namespace App\Http\ViewComponents;

class ContentLinkComponent extends PageBlockBase
{
    /**
     * @return string
     */
    public function view()
    {
        return 'blocks.content_link';
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
            'attributes' => $this->attributes,
        ];
    }
}
