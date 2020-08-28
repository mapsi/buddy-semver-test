<?php

namespace App\Http\ViewComponents;

use Illuminate\Contracts\Support\Htmlable;

interface PageBlock extends Htmlable
{
    /**
     * @return boolean
     */
    public function shouldRender();

    /**
     * @return string
     */
    public function view();
}
