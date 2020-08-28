<?php

namespace App\Http\ViewComponents\ComponentLogic;

interface ComponentLogic
{
    /**
     * @param array $layout
     * @param array $blockConfig
     * @return mixed
     */
    public function loadData(array $layout, array $blockConfig);
}
