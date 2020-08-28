<?php

namespace App\Http\ViewComponents;

class BannerWidgetComponent extends PageBlockBase
{
    /**
     * @return string
     */
    public function view()
    {
        return 'blocks.banner_link';
    }

    /**
     * @return array
     * @throws Exceptions\PageBlockSkipRender
     * @throws PageBlockWithEmptySource
     */
    public function viewData()
    {
        return [
            'attributes' => $this->attributes,
        ];
    }
}
