<?php

namespace App\Policies;

use App\Models\Report as Model;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class Report
{
    use HandlesAuthorization;

    public function show(User $user, Model $report)
    {
        return $user->isAdmin() || $user->isSubscriber('reports');
    }
}
