<?php

namespace App\Policies\Graduation;

use App\Domain\Security\Enums\Permission;
use App\Models\Graduation\DegreeAudit;
use App\Models\Person\StudentRecord;
use App\Models\User;

/**
 * Stored audits: written by the actions, seen by graduation staff and the student.
 */
class DegreeAuditPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::GraduationAudit->value) || $user->can(Permission::GraduationProcess->value);
    }

    public function view(User $user, DegreeAudit $audit): bool
    {
        return $this->viewAny($user)
            || ($user->person_id !== null && StudentRecord::query()->whereKey($audit->student_record_id)->where('person_id', $user->person_id)->exists());
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, DegreeAudit $audit): bool
    {
        return false;
    }

    public function delete(User $user, DegreeAudit $audit): bool
    {
        return false;
    }
}
