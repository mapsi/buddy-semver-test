<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Events\SubscriptionFeaturesUpdated;
use App\Models\Subscription;
use Illuminate\Console\Command;

class ExpireSubscriptions extends Command
{
    protected $signature = 'subscriptions:expire';

    protected $description = 'Expire subscriptions';

    public function handle(): void
    {
        $subscriptions = Subscription::whereDate('expiry', now()->subDay()->toDateString())->get();

        foreach ($subscriptions as $subscription) {
            event(new SubscriptionFeaturesUpdated($subscription));
        }
    }
}
