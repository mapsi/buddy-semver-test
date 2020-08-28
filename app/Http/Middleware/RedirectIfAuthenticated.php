<?php

namespace App\Http\Middleware;

use App\Models\Brand;
use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        if ($requestedFeature = $request->query('FeatureRequested')) {
            $request->session()->put('requestedFeature', $requestedFeature);
        }

        if (Auth::guard($guard)->check()) {
            return redirect(self::returnedUrl($request, $guard));
        }

        return $next($request);
    }

    public static function returnedUrl(Request $request, $guard = null)
    {
        //TODO: I'm a horrible bit of code, I know. Feel free to refactor me. Hint: Laravel After middleware perhaps?
        $user = $request->user($guard);

        if ($user) {
            if ($requestedFeature = $request->session()->pull('requestedFeature')) {
                $brand = resolve(Brand::class);
                if (! $user->hasAccessToFeatures([$requestedFeature], $brand)) {
                    return route('info.subscribe');
                }
            }

            $token = JWTAuth::fromUser($user);
            $oneDay = 1 * 24 * 60;
            Cookie::queue('gxrauth-jwt', $token, $oneDay, null, '.' . get_host_config('host'));
        }

        $returnUrl = $request->query('ReturnUrl');

        if (app()->environment('production')) {
            if (Str::contains($returnUrl, get_host_config('host'))) {
                return $returnUrl;
            }
        } else {
            if ($returnUrl) {
                return $returnUrl;
            }
        }

        return RouteServiceProvider::HOME;
    }
}
