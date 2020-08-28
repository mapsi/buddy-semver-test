<?php

namespace App\Http\Middleware;

use Closure;
use App\Classes\IpCheck;
use App\Models\Brand;
use App\Models\IpRange;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Jenssegers\Agent\Agent;
use Crawler;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\UserIpRange;

class IpAuthenticate
{
    private $whitelistedPaths = [
        '/sitemap.xml',
        '/newsmap.xml',
    ];

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($this->shouldSkipIpAuthentication($request)) {
            return $next($request);
        }

        $this->authenticateByIpAddress($request);

        return $next($request);
    }

    /**
     * @param Request $request
     * @return bool
     */
    private function shouldSkipIpAuthentication(Request $request)
    {
        return Auth::user() || $this->isAdminPage() || $this->isWhitelistedPath($request) || Crawler::isCrawler() ;
    }

    /**
     * @return bool
     */
    private function isAdminPage()
    {
        return ! app(Brand::class);
    }

    /**
     * @param Request $request
     * @return bool
     */
    private function isWhitelistedPath(Request $request)
    {
        return in_array($request->getPathInfo(), $this->whitelistedPaths);
    }

    /**
     * Attempts to authenticate the IP address against a user in the database with an active subscription to a brand.
     *
     * @param Request $request
     * @return null
     */
    public function authenticateByIpAddress($request)
    {
        $ip = $request->ip();
        $userIps = UserIpRange::validIp($ip);

        if ($userIps->count() == 0) {
            return null;
        }
        $users = User::whereIn('id', $userIps->pluck('user_id')->toArray())->get();

        $users = $users->filter(function ($user) {
            return $user->isSubscriber();
        });

        if ($users->count() == 0) {
            return null;
        }

        if ($users->count() > 1) {
            Log::info("IP authentication, multiple users match - IP addres: {$ip} , user ids: " . $users->pluck('id')->implode(', '));
        }

        Auth::login($users->first());
    }
}
