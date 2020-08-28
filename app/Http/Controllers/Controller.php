<?php

namespace App\Http\Controllers;

use Closure;
use App\Services\ContentApi\Service;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests;
    use DispatchesJobs;
    use ValidatesRequests;

    const DEFAULT_CACHE_TTL = 3;
    const DEFAULT_TRENDING_TTL = 360;

    protected $service;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->service = brandService();

            return $next($request);
        });
    }

    public function cacheResponseData(Closure $closure, int $ttl = self::DEFAULT_CACHE_TTL)
    {
        $dbt = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2)[1];

        $caller = $dbt['class'] . $dbt['function'];
        $args = $dbt['args'];

        return cacheStuff(
            $caller . json_encode($args) . 'GXR.2020-08.r2',
            $ttl,
            $closure
        );
    }
}
