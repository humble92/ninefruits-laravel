<?php

namespace App\Policies\Lms;

use App\Domain\Security\Enums\Permission;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Platforms, links and logs: lms.manage for everything.
 */
class LmsPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::LmsManage->value);
    }

    public function view(User $user, Model $model): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Model $model): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, Model $model): bool
    {
        return $this->viewAny($user);
    }
}
