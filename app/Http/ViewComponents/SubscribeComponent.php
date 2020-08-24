<?php

namespace App\Http\ViewComponents;

class SubscribeComponent extends PageBlockBase
{
    /**
     * @return string
     */
    public function view()
    {
        return $this->attributes['template'] ?? '_partials.newsletter_sign_up';
    }

    public function viewData()
    {
        return [
            'attributes' => $this->attributes,
        ];
    }
}
