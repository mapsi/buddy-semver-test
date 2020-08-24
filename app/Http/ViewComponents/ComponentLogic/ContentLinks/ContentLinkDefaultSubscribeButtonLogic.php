<?php

namespace App\Http\ViewComponents\ComponentLogic\ContentLinks;

use App\Http\ViewComponents\ComponentLogic\BaseComponentLogic;

class ContentLinkDefaultSubscribeButtonLogic extends BaseComponentLogic
{
    /**
     * @return array
     */
    public function get()
    {
        return [
            'mainText' => lang('subscription.get_more'),
            'buttonIcon' => 'envelope',
            'buttonText' => lang('subscription.sign_up'),
        ];
    }

    public function large()
    {
        return [
            'hideForLoggedIn' => auth()->check(),
            'mainText' => lang('subscription.description'),
            'secondaryText' => lang('subscription.unlimited_access'),
            'buttonText' => lang('subscribe'),
        ];
    }

    public function usa()
    {
        return [
            'mainText' => lang('subscription.usa_get_more'),
            'buttonIcon' => 'envelope',
            'buttonText' => lang('subscription.sign_up'),
        ];
    }

    public function asia()
    {
        return [
            'mainText' => lang('subscription.asia_get_more'),
            'buttonIcon' => 'envelope',
            'buttonText' => lang('subscription.sign_up'),
        ];
    }
}
