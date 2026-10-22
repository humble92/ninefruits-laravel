<?php

namespace App\Policies;

use App\Domain\Security\Enums\Permission;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::UsersView->value);
    }

    public function view(User $user, User $model): bool
    {
        return $user->can(Permission::UsersView->value);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::UsersCreate->value);
    }

    public function update(User $user, User $model): bool
    {
        return $user->can(Permission::UsersUpdate->value);
    }

    public function unlock(User $user, User $model): bool
    {
        return $user->can(Permission::UsersUpdate->value) && $user->isNot($model);
    }

    public function resetMfa(User $user, User $model): bool
    {
        return $user->can(Permission::UsersResetMfa->value) && $user->isNot($model);
    }

    /**
     * Accounts are deactivated, not deleted, so their history stays attached.
     */
    public function delete(User $user, User $model): bool
    {
        return false;
    }
}
