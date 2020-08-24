<?php

namespace App\Http\ViewComponents\ComponentLogic;

use Illuminate\Support\Arr;

trait FetchesData
{
    /**
     * @param string prefix
     * @return string
     */
    protected function getCacheKey($prefix = null)
    {
        $key = Arr::get($this->pageConfig, 'cache.key');

        if (! $key) {
            return null;
        }

        return "{$prefix}_" . sprintf($key, $this->brandSlug);
    }

    /**
     * @return mixed
     */
    protected function getCacheTtl()
    {
        return Arr::get($this->pageConfig, 'cache.ttl');
    }
}
