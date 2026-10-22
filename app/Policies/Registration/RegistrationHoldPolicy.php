<?php

namespace App\Policies\Registration;

use App\Domain\Security\Enums\Permission;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Holds and overrides.
 */
class RegistrationHoldPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::RegistrationHoldsManage->value) || $user->can(Permission::RegistrationManage->value);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::RegistrationHoldsManage->value);
    }

    public function update(User $user, Model $model): bool
    {
        return $user->can(Permission::RegistrationHoldsManage->value);
    }

    /**
     * Released or revoked, never deleted.
     */
    public function delete(User $user, Model $model): bool
    {
        return false;
    }
}
