<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Email;
use Illuminate\Auth\Access\HandlesAuthorization;

class EmailPolicy
{
    use HandlesAuthorization;

    /**
     * @param User $user
     * @return bool
     */
    public function browse(User $user)
    {
        return $this->isAdminOrEditor($user);
    }

    /**
     * @param User  $user
     * @param Email $email
     * @return bool
     */
    public function read(User $user, Email $email)
    {
        return $this->isAdminOrEditor($user) || $user->isSubscriber();
    }

    /**
     * @param User  $user
     * @param Email $email
     * @return bool
     */
    public function edit(User $user, Email $email)
    {
        return $this->isAdminOrEditor($user) && $email->isLastVersion();
    }

    /**
     * @param User  $user
     * @param Email $email
     * @return bool
     */
    public function review(User $user, Email $email)
    {
        return $this->isAdminOrEditor($user);
    }

    /**
     * @param User $user
     * @return bool
     */
    public function add(User $user)
    {
        return $this->isAdminOrEditor($user);
    }

    /**
     * @param User  $user
     * @param Email $email
     * @return bool
     */
    public function delete(User $user, Email $email)
    {
        return $this->isAdminOrEditor($user) && ! $email->is_sent;
    }

    /**
     * @param User  $user
     * @param Email $email
     * @return bool
     */
    public function send(User $user, Email $email)
    {
        return $this->isAdminOrEditor($user);
    }

    /**
     * @param User $user
     * @return bool
     */
    protected function isAdminOrEditor(User $user): bool
    {
        return $user->isAdmin() || $user->isEditor();
    }
}
