<?php

namespace App\Policies\Person;

use App\Domain\Security\Enums\Permission;
use App\Models\Person\StudentStatusChange;
use App\Models\User;

class StudentStatusChangePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::StudentStatusChangesApprove->value) || $user->can(Permission::StudentRecordsView->value);
    }

    public function view(User $user, StudentStatusChange $change): bool
    {
        return $change->studentRecord()->first()?->isVisibleTo($user) ?? false;
    }

    /**
     * Requesters cannot decide their own request (separation of duties).
     */
    public function decide(User $user, StudentStatusChange $change): bool
    {
        return $user->can(Permission::StudentStatusChangesApprove->value)
            && $change->requested_by_id !== $user->id
            && $this->view($user, $change);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, StudentStatusChange $change): bool
    {
        return false;
    }

    public function delete(User $user, StudentStatusChange $change): bool
    {
        return false;
    }
}
