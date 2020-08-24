<?php

namespace App\Http\Middleware;

use Closure;

class ApiCmsAuthentication
{
    /**
     * Handle an incoming request.
     * A token looks like this: 5b604974ad059.1533036916.18ee2142e451ab901c227d7cf4509896
     * <random identifier>.<gmt timestamp>.<md5([identifier].[timestamp].[secret])>
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        /* Check the header */
        if (! $request->headers->has('authorization')) {
            abort(403, 'No authorization header.');
        }

        $authorization_header = $request->headers->get('authorization');

        if (strpos($authorization_header, 'Bearer') !== 0) {
            abort(403, 'Unknown authorization type.');
        }

        /* Check the token */
        $token = substr($authorization_header, strlen('Bearer '));

        $exploded_token = explode('.', $token);

        if (count($exploded_token) !== 3) {
            abort(403, 'Invalid token format.');
        }

        list($transaction_id, $timestamp, $hash) = $exploded_token;

        if (! $transaction_id || ! ctype_digit($timestamp) || strlen($hash) !== 32) {
            abort(403, 'Invalid token.');
        }

        $secret = config('globemedia.drupal.authentication_secret');

        /* Check the hash */
        if ($hash !== md5($transaction_id . '.' . $timestamp . '.' . $secret)) {
            abort(403, 'Invalid hash.');
        }

        /** Don't check for expired hash on environments with debug on */
        if (! app()->environment(['dev', 'local'])) {
            if (abs(gmdate('U') - $timestamp) > 60) {
                abort(403, 'Expired hash.');
            }
        }

        return $next($request);
    }
}
