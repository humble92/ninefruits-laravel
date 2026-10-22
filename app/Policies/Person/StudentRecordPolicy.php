<?php

namespace App\Policies\Person;

use App\Domain\Security\Enums\Permission;
use App\Models\Person\StudentRecord;
use App\Models\User;

class StudentRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::StudentRecordsView->value) || $user->can(Permission::StudentRecordsManage->value);
    }

    public function view(User $user, StudentRecord $record): bool
    {
        return $record->isVisibleTo($user);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::StudentRecordsManage->value);
    }

    public function update(User $user, StudentRecord $record): bool
    {
        return $record->isManageableBy($user);
    }

    public function changeStatus(User $user, StudentRecord $record): bool
    {
        return $user->can(Permission::StudentStatusChangesApprove->value) && $record->isVisibleTo($user);
    }

    public function assignAdvisor(User $user, StudentRecord $record): bool
    {
        return $user->can(Permission::AdvisorsManage->value) && $record->isVisibleTo($user);
    }

    /**
     * Student records are never deleted; status changes end them.
     */
    public function delete(User $user, StudentRecord $record): bool
    {
        return false;
    }
}
