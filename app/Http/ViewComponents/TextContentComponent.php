<?php

namespace App\Http\ViewComponents;

class TextContentComponent extends PageBlockBase
{
    /**
     * @return string
     */
    public function view()
    {
        return 'blocks.text_content';
    }

    /**
     * @return array
     */
    public function loadData()
    {
        return ['text' => lang('about_us.text')];
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
