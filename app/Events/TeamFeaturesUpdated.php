<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Team;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TeamFeaturesUpdated
{
    use Dispatchable;
    use SerializesModels;

    public $model;

    public function __construct(Team $model)
    {
        $this->model = $model;
    }
}
