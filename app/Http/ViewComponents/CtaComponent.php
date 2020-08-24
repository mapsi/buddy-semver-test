<?php

namespace App\Http\ViewComponents;

class CtaComponent extends PageBlockBase
{
    const SLUG = 'cta';

    /**
     * @return array
     * @throws Exceptions\PageBlockSkipRender
     */
    public function loadData()
    {
        $this->shouldRender();

        return ['type' => $this->args['type']];
    }

    /**
     * @return string
     */
    public function view()
    {
        return 'blocks.cta';
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
