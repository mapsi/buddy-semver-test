<?php

declare(strict_types=1);

namespace App\Models\Policies;

use App\Models\User;
use App\Models\SubscriptionLevel;
use Illuminate\Auth\Access\HandlesAuthorization;

class SubscriptionLevelPolicy
{
    use HandlesAuthorization;

  /**
   * Determine whether the user can view any subscriptionLevels.
   *
   * @param  \App\Models\User  $user
   * @return mixed
   */
    public function viewAny(User $user)
    {
        return ($user->isAdmin() || $user->isSubscriptionManager());
    }

  /**
   * Determine whether the user can view the subscriptionLevel.
   *
   * @param  \App\Models\User  $user
   * @param  \App\SubscriptionLevel  $subscriptionLevel
   * @return mixed
   */
    public function view(User $user, SubscriptionLevel $subscriptionLevel): bool
    {
        return ($user->isAdmin() || $user->isSubscriptionManager());
    }

  /**
   * Determine whether the user can create subscriptionLevels.
   *
   * @param  \App\Models\User  $user
   * @return mixed
   */
    public function create(User $user)
    {
        return ($user->isAdmin() || $user->isSubscriptionManager());
    }

  /**
   * Determine whether the user can update the subscriptionLevel.
   *
   * @param  \App\Models\User  $user
   * @param  \App\SubscriptionLevel  $subscriptionLevel
   * @return mixed
   */
    public function update(User $user, SubscriptionLevel $subscriptionLevel)
    {
        return ($user->isAdmin() || $user->isSubscriptionManager());
    }

  /**
   * Determine whether the user can delete the subscriptionLevel.
   *
   * @param  \App\Models\User  $user
   * @param  \App\SubscriptionLevel  $subscriptionLevel
   * @return mixed
   */
    public function delete(User $user, SubscriptionLevel $subscriptionLevel)
    {
        return ($user->isAdmin() || $user->isSubscriptionManager());
    }

  /**
   * Determine whether the user can restore the subscriptionLevel.
   *
   * @param  \App\Models\User  $user
   * @param  \App\SubscriptionLevel  $subscriptionLevel
   * @return mixed
   */
    public function restore(User $user, SubscriptionLevel $subscriptionLevel)
    {
      //
    }

  /**
   * Determine whether the user can permanently delete the subscriptionLevel.
   *
   * @param  \App\Models\User  $user
   * @param  \App\SubscriptionLevel  $subscriptionLevel
   * @return mixed
   */
    public function forceDelete(User $user, SubscriptionLevel $subscriptionLevel)
    {
      //
    }
}
