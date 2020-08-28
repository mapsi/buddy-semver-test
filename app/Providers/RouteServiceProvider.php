<?php

namespace App\Providers;

use App\Models\Brand;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to your controller routes.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'App\Http\Controllers';

    /**
     * The path to the "home" route for your application.
     *
     * @var string
     */
    public const HOME = '/';

    private $brand;

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @param Brand|null $brand
     * @return void
     */
    public function boot(Brand $brand = null)
    {
        $this->brand = $brand;

        parent::boot();
        if (! $brand) {
            config([
                'auth.defaults.guard' => 'admin',
            ]);
        }
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        $this->mapApiRoutes();

        $this->mapSharedRoutes(); //as only controllers can add middleware with an except
        if ($this->brand) {
            $this->mapWebRoutes();
            $this->mapBrandRoutes();
        } else {
            $this->mapAdminRoutes();
        }
    }

    protected function mapSharedRoutes()
    {
        $middlewares = ['web'];

        // Due to the wayJrean\UserVerification works, we have to explicitly set these routes or `php artisan route:list` will fail.
        Route::get('email-verification/error', $this->namespace . '\Auth\RegisterController@getVerificationError')
            ->name('email-verification.error')
            ->middleware($middlewares);

        Route::get('email-verification/check/{token}', $this->namespace . '\Auth\RegisterController@getVerification')
            ->name('email-verification.check')
            ->middleware($middlewares);

        Route::middleware($middlewares)
            ->namespace($this->namespace)
            ->group(base_path('routes/shared.php'));
    }

    /**
     * Define the "api" routes for the application.
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        Route::prefix('api')
            ->middleware(['api'])
            ->namespace($this->namespace)
            ->group(base_path('routes/api.php'));
    }

    /**
     * Define the "web" routes for the application.
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapAdminRoutes()
    {
        Route::middleware(['web'])
            ->namespace($this->namespace . '\Admin')
            ->group(base_path('routes/admin.php'));
    }

    /**
     * Define the "web" routes for the application.
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapWebRoutes()
    {
        Route::middleware(['web', 'isVerified'])
            ->namespace($this->namespace)
            ->group(base_path('routes/web.php'));
        Route::middleware(['api'])
            ->namespace($this->namespace)
            ->group(base_path('routes/feeds.php'));
    }

    protected function mapBrandRoutes()
    {
        $routefile = base_path("brands/{$this->brand->machine_name}/routes/web.php");

        if (file_exists($routefile)) {
            Route::middleware(['web', 'isVerified'])
                ->namespace($this->namespace)
                ->group($routefile);
        }
    }
}
