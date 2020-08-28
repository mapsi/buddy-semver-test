<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class Admin
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function index(User $user)
    {
        // User can access admin panel
        if ($user->admin > 0) {
            return true;
        }
    }

    public function indexAdmin(User $user)
    {
        return $user->isAdmin();
    }

    public function indexUsers(User $user)
    {
        if ($user->isAdmin() || $user->isSubscriptionManager()) {
            return true;
        }
    }

    public function indexProducts(User $user)
    {
        if ($user->isAdmin() || $user->isSubscriptionManager()) {
            return true;
        }
    }

    public function indexCoupons(User $user)
    {
        if ($user->isAdmin() || $user->isSubscriptionManager()) {
            return true;
        }
    }

    public function indexSubscriptions(User $user)
    {
        if ($user->isAdmin() || $user->isSubscriptionManager()) {
            return true;
        }
    }

    public function indexTeams(User $user)
    {
        if ($user->isAdmin() || $user->isSubscriptionManager()) {
            return true;
        }
    }

    public function admin(User $user)
    {
        return $user->isAdmin();
    }

    public function editor(User $user)
    {
        return $user->isAdmin() || $user->isEditor();
    }

    public function subscriptionManage(User $user)
    {
        return $user->isAdmin() || $user->isSubscriptionManager();
    }

    public function editUser(User $user, User $userToEdit)
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isSubscriptionManager() && ! $userToEdit->isAdmin()) {
            // Subscription managers can't edit admins
            return true;
        }
    }
}
