<?php

namespace App\Policies\Grading;

use App\Domain\Security\Enums\Permission;
use App\Models\Grading\GradeChangeRequest;
use App\Models\User;

/**
 * Requests are made from the grade entry screen and decided through ManageGradeChanges;
 * the admin list shows and decides them.
 */
class GradeChangeRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::GradeChangesApprove->value) || $user->can(Permission::GradeChangesRequest->value) || $user->can(Permission::GradesView->value);
    }

    public function view(User $user, GradeChangeRequest $request): bool
    {
        return $this->viewAny($user) || $request->requested_by_id === $user->id;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, GradeChangeRequest $request): bool
    {
        return false;
    }

    public function delete(User $user, GradeChangeRequest $request): bool
    {
        return false;
    }
}
