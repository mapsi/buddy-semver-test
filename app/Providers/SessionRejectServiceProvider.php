<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Cookie;

class SessionRejectServiceProvider extends ServiceProvider
{

    public function register()
    {
        $me = $this;

        $this->app->bind('session.reject', function ($app) use ($me) {
            return function ($request) use ($me) {
                return call_user_func_array(array($me, 'reject'), array($request));
            };
        });
    }

    // Put the guts of whatever you want to do in here, in this case I've
    // disabled sessions for every request that is an Ajax request, you
    // could do something else like check the path against a list and
    // selectively return true if there's a match.
    protected function reject($request)
    {

        return Cookie::get(config('session.cookie')) === null;
    }
}
