<?php

namespace App\Http\ViewComponents\ComponentLogic;

class TeamListLogic extends BaseComponentLogic
{
    use FetchesData;

    /**
     * @return array
     */
    public function get()
    {
        return [
            'title' => lang('meet_the_team.title'),
            'subTitle' => lang('meet_the_team.subtitle'),
            'authors' => $this->attribute('authors', []),
        ];
    }
}
