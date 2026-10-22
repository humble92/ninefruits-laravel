<?php

namespace App\Policies\Person;

use App\Domain\Security\Enums\Permission;
use App\Models\Person\Instructor;
use App\Models\User;

class InstructorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::InstructorsManage->value)
            || $user->can(Permission::PeopleView->value)
            || $user->can(Permission::AdvisorsManage->value);
    }

    public function view(User $user, Instructor $instructor): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::InstructorsManage->value);
    }

    public function update(User $user, Instructor $instructor): bool
    {
        return $user->can(Permission::InstructorsManage->value);
    }

    public function delete(User $user, Instructor $instructor): bool
    {
        return false;
    }
}
