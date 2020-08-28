<?php

namespace App\Http\ViewComponents;

class CtaPreFooterColourComponent extends PageBlockBase
{
    const SLUG = 'cta_prefooter_colour';

    /**
     * @return array
     * @throws Exceptions\PageBlockSkipRender
     */
    public function loadData()
    {
        $this->shouldRender();

        return $this->args;
    }

    /**
     * @return string
     */
    public function view()
    {
        return 'blocks.cta_prefooter_colour';
    }

    /**
     * @return array
     * @throws Exceptions\PageBlockSkipRender
     */
    public function viewData()
    {
        return [
            'data' => $this->loadData(),
            'attributes' => $this->layout->getAttributesFromBlock($this->blockName()),
        ];
    }
}
