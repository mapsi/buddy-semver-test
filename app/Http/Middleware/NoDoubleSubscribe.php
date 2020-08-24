<?php

namespace App\Http\Middleware;

use Closure;
use Laracasts\Flash\Flash;

class NoDoubleSubscribe
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = auth()->user();
        if ($user) {
            if ($user->isSubscriber()) {
                Flash::error('You are already subscribed.');
                return Response()->redirectToRoute('subscriber.index');
            } elseif ($user->teams->first()) {
                if ($user->teams->first()->user_id != $user->id) {
                    Flash::error('You are not the manager of your team please contact them to change your subscription.');
                    return Response()->redirectToRoute('subscriber.index');
                } elseif ($user->teams->first()->members()->count() > 1) {
                    Flash::error('You are a corporate subscription please contact us for subscriptions.');
                    return Response()->redirectToRoute('subscriber.index');
                }
            }
        }
        return $next($request);
    }
}
