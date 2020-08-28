<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Subscription;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubscriptionFeaturesUpdated
{
    use Dispatchable;
    use SerializesModels;

    public $model;

    public function __construct(Subscription $model)
    {
        $this->model = $model;
    }
}
