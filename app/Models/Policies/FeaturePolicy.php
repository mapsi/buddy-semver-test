<?php

declare(strict_types=1);

namespace App\Models\Policies;

use App\Models\User;
use App\Models\Feature;
use Illuminate\Auth\Access\HandlesAuthorization;

class FeaturePolicy
{
    use HandlesAuthorization;

  /**
   * Determine whether the user can view any features.
   *
   * @param  \App\Models\User  $user
   * @return mixed
   */
    public function viewAny(User $user): bool
    {
        return ($user->isAdmin() || $user->isSubscriptionManager());
    }

  /**
   * Determine whether the user can view the feature.
   *
   * @param  \App\Models\User  $user
   * @param  \App\Feature  $feature
   * @return mixed
   */
    public function view(User $user, Feature $feature): bool
    {
        return ($user->isAdmin() || $user->isSubscriptionManager());
    }

  /**
   * Determine whether the user can create features.
   *
   * @param  \App\Models\User  $user
   * @return mixed
   */
    public function create(User $user): bool
    {
        return ($user->isAdmin() || $user->isSubscriptionManager());
    }

  /**
   * Determine whether the user can update the feature.
   *
   * @param  \App\Models\User  $user
   * @param  \App\Feature  $feature
   * @return mixed
   */
    public function update(User $user, Feature $feature): bool
    {
        return ($user->isAdmin() || $user->isSubscriptionManager());
    }

  /**
   * Determine whether the user can delete the feature.
   *
   * @param  \App\Models\User  $user
   * @param  \App\Feature  $feature
   * @return mixed
   */
    public function delete(User $user, Feature $feature): bool
    {
        return ($user->isAdmin() || $user->isSubscriptionManager());
    }

  /**
   * Determine whether the user can restore the feature.
   *
   * @param  \App\Models\User  $user
   * @param  \App\Feature  $feature
   * @return mixed
   */
    public function restore(User $user, Feature $feature): bool
    {
        return false;
    }

  /**
   * Determine whether the user can permanently delete the feature.
   *
   * @param  \App\Models\User  $user
   * @param  \App\Feature  $feature
   * @return mixed
   */
    public function forceDelete(User $user, Feature $feature): bool
    {
        return false;
    }
}
