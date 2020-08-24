<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        \App\Events\ContentViewed::class => [
            \App\Listeners\ContentViewed::class,
        ],
        \App\Events\UserSaved::class => [
            \App\Listeners\UserSaved::class,
        ],

        \App\Events\SubscriptionLevelFeaturesUpdated::class => [
            \App\Listeners\UpdateSubscriptionFeatures::class,
        ],
        \App\Events\SubscriptionFeaturesUpdated::class => [
            \App\Listeners\UpdateTeamFeatures::class,
        ],
        \App\Events\TeamFeaturesUpdated::class => [
            \App\Listeners\UpdateUserFeatures::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }
}
