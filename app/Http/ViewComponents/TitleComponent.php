<?php

namespace App\Http\ViewComponents;

class TitleComponent extends PageBlockBase
{
    /**
     * @return string
     */
    public function view()
    {
        return 'blocks.page_title';
    }

    /**
     * @return array
     * @throws Exceptions\PageBlockSkipRender
     */
    public function viewData()
    {
        return ['data' => $this->loadData()];
    }
}
