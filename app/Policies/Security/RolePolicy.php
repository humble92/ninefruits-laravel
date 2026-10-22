<?php

namespace App\Policies\Security;

use App\Domain\Security\Enums\Permission;
use App\Models\Security\Role;
use App\Models\User;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::RolesView->value);
    }

    public function view(User $user, Role $role): bool
    {
        return $user->can(Permission::RolesView->value);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::RolesManage->value);
    }

    public function update(User $user, Role $role): bool
    {
        return $user->can(Permission::RolesManage->value);
    }

    public function delete(User $user, Role $role): bool
    {
        return $user->can(Permission::RolesManage->value)
            && ! $role->is_system
            && ! $role->isPersona()
            && ! $role->assignments()->exists();
    }
}
