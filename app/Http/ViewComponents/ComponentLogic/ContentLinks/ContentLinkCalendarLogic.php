<?php

namespace App\Http\ViewComponents\ComponentLogic\ContentLinks;

use App\Http\ViewComponents\ComponentLogic\BaseComponentLogic;

class ContentLinkCalendarLogic extends BaseComponentLogic
{
    /**
     * @return array
     */
    public function get()
    {
        return [
            'link' => '/info/editorial-calendar',
            'icon' => '<i class="far fa-calendar-alt fa-2x"></i>',
            'mainText' => lang('calendar_link.main_text'),
            'extraText' => lang('calendar_link.additional_text'),
        ];
    }
}
