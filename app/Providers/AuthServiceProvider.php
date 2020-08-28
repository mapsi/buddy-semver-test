<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Model' => 'App\Policies\ModelPolicy', // TODO: What is this? Remove it?
        \App\Models\Coupon::class => \App\Policies\Admin::class,
        \App\Models\Email::class => \App\Policies\EmailPolicy::class,
        \App\Services\ContentApi\Entities\Magazine::class => \App\Policies\Magazine::class,
        \App\Models\Product::class => \App\Policies\Admin::class,
        \App\Models\Subscription::class => \App\Policies\Admin::class,
        \App\Models\Team::class => \App\Policies\Admin::class,
        \App\Models\User::class => \App\Policies\Admin::class,
        \App\Models\DirectoryIndividual::class => \App\Policies\Subscriber::class,
        \App\Models\DirectoryFirm::class => \App\Policies\Subscriber::class,
        \App\Models\Directory::class => \App\Policies\Subscriber::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        \Illuminate\Support\Facades\Auth::provider('customuserprovider', function ($app, array $config) {
            return new CustomUserProvider($app['hash'], $config['model']);
        });

        //
    }
}
