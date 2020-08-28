<?php

namespace App\Http\ViewComponents;

class TiplineComponentSmall extends PageBlockBase
{
    /**
     * @return string
     */
    public function view()
    {
        return 'blocks.content_link_tipline_small';
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
