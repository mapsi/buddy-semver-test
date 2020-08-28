<?php

namespace App\Http\ViewComponents\ComponentLogic;

class JumbotronLogic extends BaseComponentLogic
{
    /**
     * @return array
     */
    public function get()
    {
        return $this->attribute('jumbotron');
    }
}
