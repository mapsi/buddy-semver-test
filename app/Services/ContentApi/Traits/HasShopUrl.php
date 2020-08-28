<?php

namespace App\Services\ContentApi\Traits;

trait HasShopUrl
{
    public function getShopUrlTitle()
    {
        return $this->getShopUrl() ? $this->getShopUrl()['Title'] : null;
    }

    public function getShopUrlUri()
    {
        return ($this->getShopUrl() && filter_var($this->getShopUrl()['Uri'], FILTER_VALIDATE_URL)) ? $this->getShopUrl()['Uri'] : null;
    }

    public function getShopUrl()
    {
        return $this->getInfo('ShopUrl') ?? [];
    }
}
