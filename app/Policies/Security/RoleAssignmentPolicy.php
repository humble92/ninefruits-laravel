<?php

namespace App\Policies\Security;

use App\Domain\Security\Enums\Permission;
use App\Models\Security\RoleAssignment;
use App\Models\User;

class RoleAssignmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::UsersView->value);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::RoleAssignmentsManage->value);
    }

    /**
     * Ending an assignment. Users cannot change their own assignments (separation of duties).
     */
    public function revoke(User $user, RoleAssignment $assignment): bool
    {
        return $user->can(Permission::RoleAssignmentsManage->value)
            && $assignment->user_id !== $user->id
            && $assignment->isActive();
    }
}
