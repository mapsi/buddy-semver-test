<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\SubscriptionLevel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubscriptionLevelFeaturesUpdated
{
    use Dispatchable;
    use SerializesModels;

    public $model;

    public function __construct(SubscriptionLevel $model)
    {
        $this->model = $model;
    }
}
