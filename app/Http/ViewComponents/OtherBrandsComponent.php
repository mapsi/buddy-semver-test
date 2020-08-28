<?php

namespace App\Http\ViewComponents;

class OtherBrandsComponent extends PageBlockBase
{
    /**
     * @return string
     */
    public function view()
    {
        return 'blocks.other_brands';
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
