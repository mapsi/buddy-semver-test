<?php

namespace App\Providers;

use App\Services\ContentApi\Service;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class ContentApiServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $this->app->singleton(Service::class, function () {
            return brandService();
        });
    }

    public function provides()
    {
        return [
            Service::class,
        ];
    }
}
