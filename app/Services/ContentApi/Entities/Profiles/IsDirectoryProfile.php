<?php

declare(strict_types=1);

namespace App\Services\ContentApi\Entities\Profiles;

use App\Classes\BotDetect;
use App\Models\SubscriptionLevel;
use App\Models\User;

trait IsDirectoryProfile
{
    public function isFree(): bool
    {
        return (bool) $this->getInfo('FreeToAccess');
    }

    public function canView(User $user): bool
    {
        if ($this->isFree()) {
            return true;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if (SubscriptionLevel::usingThis(active_host())) {
            return $this->comparePermissions($user);
        }

        if ($user->isSubscriber()) {
            return true;
        }

        if (
            get_host_config('subscribed_to_login', false)
            && $user->isVerified()
        ) {
            return true;
        }

        $botdetect = new BotDetect(request());
        if ($botdetect->validate()) {
            return true;
        }

        return false;
    }
}
