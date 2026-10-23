<?php

namespace App\Policies\Graduation;

use App\Domain\Security\Enums\Permission;
use App\Models\Person\StudentRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Stored audits and waivers: written by the actions, seen by graduation staff and the student.
 */
class DegreeAuditPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::GraduationAudit->value) || $user->can(Permission::GraduationProcess->value);
    }

    public function view(User $user, Model $model): bool
    {
        return $this->viewAny($user)
            || ($user->person_id !== null && StudentRecord::query()->whereKey($model->getAttribute('student_record_id'))->where('person_id', $user->person_id)->exists());
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
