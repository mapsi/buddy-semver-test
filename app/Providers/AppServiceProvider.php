<?php

namespace App\Providers;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use MadWeb\Robots\RobotsFacade;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(UrlGenerator $url)
    {
        $this->registerBladeDirectives();
        $this->extendValidator();
        $this->registerCollectionMacros();
        $this->configureDatabase();
        $this->createSegmentVariables();
        $this->setUpRobots();
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $me = $this;

        $this->app->bind('session.reject', function ($app) use ($me) {
            return function ($request) use ($me) {
                return call_user_func_array([$me, 'reject'], [$request]);
            };
        });
    }

    // Put the guts of whatever you want to do in here, in this case I've
    // disabled sessions for every request that is an Ajax request, you
    // could do something else like check the path against a list and
    // selectively return true if there's a match.
    protected function reject($request)
    {
        return $request->ajax();
    }

    /**
     * @return void
     */
    private function registerBladeDirectives()
    {
        Blade::directive('fixhtml', function ($expression) {
            return fix_html($expression);
        });

        Blade::if('env', function ($environment) {
            return app()->environment($environment);
        });
    }

    /**
     * @return void
     */
    private function extendValidator()
    {
        Validator::extend('CDIR', function ($attribue, $value, $parameters) {
            $if = filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
            if ($if) {
                return true;
            }

            $bits = explode('-', $value);
            if (count($bits) == 2 && filter_var($bits[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false && filter_var($bits[1], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
                return true;
            }

            $parts = explode('/', $value);

            if (count($parts) != 2 || ip2long($parts[0]) === false) {
                return false;
            }

            return true;
        });
        Validator::extend('min_filled', function ($attribute, $value, $parameters, $validator) {
            if (empty($parameters[0]) || ! ctype_digit($parameters[0])) {
                throw new InvalidArgumentException();
            }

            if (! is_array($value)) {
                return false;
            }

            if (count(array_filter($value)) < $parameters[0]) {
                return false;
            }

            return true;
        });
        Validator::replacer('min_filled', function ($message, $attribute, $rule, $parameters) {
            return str_replace([':num'], $parameters, $message);
        });
    }

    /**
     * @return void
     */
    private function registerCollectionMacros()
    {
        Collection::macro('format', function ($fields = ['id', 'name']) {
            return $this->map(function ($value) use ($fields) {

                $array = [];
                foreach ($fields as $field) {
                    $array[$field] = (is_object($value) ? $value->$field : $value[$field]);
                }

                return $array;
            });
        });

        /**
         * Paginate a standard Laravel Collection.
         * @see https://gist.github.com/simonhamp/549e8821946e2c40a617c85d2cf5af5e
         *
         * @param int $perPage
         * @param int $total
         * @param int $page
         * @param string $pageName
         * @return array
         */
        Collection::macro('paginate', function ($perPage, $total = null, $page = null, $pageName = 'page') {
            $page = $page ?: LengthAwarePaginator::resolveCurrentPage($pageName);

            return new LengthAwarePaginator(
                $this->forPage($page, $perPage),
                $total ?: $this->count(),
                $perPage,
                $page,
                [
                    'path' => LengthAwarePaginator::resolveCurrentPath(),
                    'pageName' => $pageName,
                ]
            );
        });
    }

    /**
     * @return void
     */
    private function configureDatabase()
    {
        Schema::defaultStringLength(191);
    }

    private function createSegmentVariables()
    {
        View::composer('*', function ($view) {
            $segmentData = [];
            if (isset($view->entity) && $view->entity instanceof \App\Services\ContentApi\Entities\DataEntity) {
                try {
                    $segmentData['entity_api_id'] = $view->entity->getId();
                    $segmentData['entity_api_type'] = $view->entity->getEntityType();
                    $segmentData['entity_api_title'] = $view->entity->getTitle();
                    if ($user = auth()->user()) {
                        if (method_exists($view->entity, 'isFree') && ! $view->entity->isFree()) {
                            $segmentData['userid'] = $user->id;
                            $segmentData['useremail'] = $user->email;
                            if ($userDetail = $user->lbrDetail) {
                                $segmentData['lbr_account_id'] = $userDetail->lbr_account_id;
                                $segmentData['lbr_user_id'] = $userDetail->lbr_user_id;
                                $segmentData['lbr_organisation_id'] = $userDetail->lbr_organisation_id;
                            }
                        }
                    }
                } catch (\Exception $e) {
                    report($e);
                }
            }
            $view->with('segmentData', $segmentData);
        });
    }

    private function setUpRobots(): void
    {
        RobotsFacade::setShouldIndexCallback(function () {
            return app()->environment('production');
        });
    }
}
