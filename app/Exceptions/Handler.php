<?php

namespace App\Exceptions;

use App\Models\Brand;
use Exception;
use Http\Client\Exception\HttpException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;
use Jrean\UserVerification\Exceptions\UserNotVerifiedException;
use Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        UserNotVerifiedException::class,
    ];

    protected $internalDontReport = [
        AuthenticationException::class,
        AuthorizationException::class,
        HttpException::class,
        HttpResponseException::class,
        ModelNotFoundException::class,
        SuspiciousOperationException::class,
        ValidationException::class,
        NotFoundHttpException::class,
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param \Exception $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        if (app()->bound('sentry') && env('APP_ENV') != 'local' && $this->shouldReport($exception)) {
            app('sentry')->captureException($exception);
        }

        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Exception               $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        if (
            ! active_host('admin')
            && ($exception instanceof AuthorizationException)
            && (! get_host_config('subscribed_to_login', false))
        ) {
            return redirect()->route('subscribe.why');
        }


        if ($exception instanceof UserNotVerifiedException) {
            return response(view('auth.verify'));
        }

        return parent::render($request, $exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Exception               $exception
     * @return \Illuminate\Http\Response
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $exception->getMessage()], 401);
        }

        if (app(Brand::class) && ! get_host_config('subscribed_to_login', false)) {
            if (
                (strpos('my-account', $request->route()->uri) === 0)
                || ($request->route()->getName() === 'preview')
            ) {
                return redirect()->guest(route('login'));
            }

            return redirect()->guest(route('subscribe.why'));
        }

        return redirect()->guest(route('login'));
    }
}
