<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;

class ApiAuditAuthentication
{
    /**
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (auth()->user() && auth()->user()->isAdmin()) {
            return $next($request);
        }

        /** Events API access */
        if ($request->header('ApiKey') == config('globemedia.events.api_key')) {
            return $next($request);
        }

        abort(JsonResponse::HTTP_FORBIDDEN);
    }
}
