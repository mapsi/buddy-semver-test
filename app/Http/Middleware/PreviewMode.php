<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PreviewMode
{
    public function handle(Request $request, Closure $next)
    {
        if ($user = Auth::user()) {
            if ($user->isEditor()) {
                if ($request->has('preview')) {
                    $preview = (bool) intval($request->get('preview', 0));
                    $request->session()->put('preview', $preview);
                }
            }
        }

        return $next($request);
    }
}
