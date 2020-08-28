<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\SubscriptionFeaturesUpdated;
use App\Events\SubscriptionLevelFeaturesUpdated;
use App\Models\Subscription;

class UpdateSubscriptionFeatures
{
    public function handle(SubscriptionLevelFeaturesUpdated $event)
    {
        $event->model->subscriptions()->isEnabled()->get()->each(function (Subscription $subscription) {
            event(new SubscriptionFeaturesUpdated($subscription));
        });
    }
}
