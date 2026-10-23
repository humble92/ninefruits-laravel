<?php

namespace App\Policies\Assessment;

use App\Domain\Security\Enums\Permission;
use App\Models\Assessment\AssessmentResultCorrection;
use App\Models\Person\StudentRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Registrations and results: written through the actions, seen by staff who record and by
 * the student they belong to.
 */
class AssessmentRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::AssessmentsRecord->value) || $user->can(Permission::AssessmentsManage->value);
    }

    public function view(User $user, Model $model): bool
    {
        $recordId = $model instanceof AssessmentResultCorrection ? $model->result->student_record_id : $model->getAttribute('student_record_id');

        return $this->viewAny($user)
            || ($user->person_id !== null && StudentRecord::query()->whereKey($recordId)->where('person_id', $user->person_id)->exists());
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
