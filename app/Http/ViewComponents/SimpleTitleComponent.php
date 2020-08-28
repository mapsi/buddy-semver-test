<?php

namespace App\Http\ViewComponents;

class SimpleTitleComponent extends PageBlockBase
{
    /**
     * @return string
     */
    public function view()
    {
        return 'blocks.simple_page_title';
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
