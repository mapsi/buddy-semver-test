<?php

namespace App\Http\ViewComponents;

class CtaPreFooterComponent extends PageBlockBase
{
    const SLUG = 'cta_prefooter';

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
        return 'blocks.cta_prefooter';
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
