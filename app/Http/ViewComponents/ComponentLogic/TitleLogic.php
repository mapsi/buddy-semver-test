<?php

namespace App\Http\ViewComponents\ComponentLogic;

class TitleLogic extends BaseComponentLogic
{
    /**
     * @return array
     */
    public function get()
    {
        $pageTitle = $this->attributes['page_title'];

        return array_merge(
            get_host_config('subscription'),
            [
                'sign_up' => lang('subscription.sign_up'),
                'title' => is_callable($pageTitle) ? $pageTitle() : $pageTitle,
            ]
        );
    }
}
