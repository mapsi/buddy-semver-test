<?php

namespace App\Http\ViewComponents;

class TeamListComponent extends PageBlockBase
{
    /**
     * @return string
     */
    public function view()
    {
        return 'blocks.team_list';
    }

    /**
     * @return array
     * @throws Exceptions\PageBlockSkipRender
     */
    public function viewData()
    {
        return [
            'data' => $this->loadData(),
            'attributes' => $this->attributes,
        ];
    }
}
