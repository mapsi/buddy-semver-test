<?php

namespace App\Http\ViewComponents;

class TwitterComponent extends PageBlockBase
{
    const SLUG = 'twitter';

    /**
     * @return array
     * @throws Exceptions\PageBlockSkipRender
     */
    public function loadData()
    {
        $this->shouldRender();

        return [];
    }

    /**
     * @return string
     */
    public function view()
    {
        return 'blocks.twitter';
    }

    /**
     * @return array
     * @throws Exceptions\PageBlockSkipRender
     */
    public function viewData()
    {
        return [
            'data' => $this->loadData(),
            'attributes' => $this->layout->getAttributesFromBlock(static::SLUG),
        ];
    }
}
