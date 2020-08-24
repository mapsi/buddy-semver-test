<?php

namespace App\Http\ViewComponents;

class SubFeaturedComponent extends PageBlockBase
{
    /**
     * @return string
     */
    public function view()
    {
        return 'blocks.sub_featured';
    }

    /**
     * @return array
     * @throws Exceptions\PageBlockSkipRender
     * @throws PageBlockWithEmptySource
     */
    public function viewData()
    {
        return [
            'articles' => $this->loadData()->hydrate(),
            'attributes' => $this->attributes,
        ];
    }
}
