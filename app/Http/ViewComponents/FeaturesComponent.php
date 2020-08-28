<?php

namespace App\Http\ViewComponents;

class FeaturesComponent extends PageBlockBase
{
    /**
     * @return string
     */
    public function view()
    {
        return 'blocks.features';
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
