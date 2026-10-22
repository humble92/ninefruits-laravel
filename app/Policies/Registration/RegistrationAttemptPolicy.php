<?php

namespace App\Policies\Registration;

use App\Domain\Security\Enums\Permission;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * The attempt log is read only.
 */
class RegistrationAttemptPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::RegistrationManage->value) || $user->can(Permission::EnrollmentsView->value);
    }

    public function view(User $user, Model $model): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Model $model): bool
    {
        return false;
    }

    public function delete(User $user, Model $model): bool
    {
        return false;
    }
}
