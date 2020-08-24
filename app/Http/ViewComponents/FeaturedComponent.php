<?php

namespace App\Http\ViewComponents;

class FeaturedComponent extends PageBlockBase
{
    /**
     * @return string
     */
    public function view()
    {
        return 'blocks.featured_gdr';
    }

    /**
     * @return array
     * @throws Exceptions\PageBlockSkipRender
     */
    public function viewData()
    {
        return [
            'articles' => $this->loadData()->hydrate(),
            'attributes' => $this->attributes,
        ];
    }
}
