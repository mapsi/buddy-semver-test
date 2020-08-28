<?php

namespace App\Http\ViewComponents\ComponentLogic;

use App\Services\ContentApi\Results;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class CachedComponentLogic implements ComponentLogic
{
    protected $component;
    protected $ttl;

    public function __construct(BaseComponentLogic $component, int $ttl = 0)
    {
        $this->component = $component;
        $this->ttl = $ttl;
    }

    public function loadData(array $pageConfig, array $blockConfig)
    {
        $queryHash = md5(json_encode(Arr::only($blockConfig['attributes'], ['query', 'cache_ttl'])));

        $cached = cacheStuff($queryHash, $this->ttl, function () use ($pageConfig, $blockConfig) {
            return $this->loadComponentData($pageConfig, $blockConfig);
        });

        return new Results(['items' => $cached]);
    }

    private function loadComponentData(array $pageConfig, array $blockConfig)
    {
        return $this->component->loadData($pageConfig, $blockConfig)->hydrate()->toArray();
    }
}
