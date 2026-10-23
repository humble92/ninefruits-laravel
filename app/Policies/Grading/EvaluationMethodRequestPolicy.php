<?php

namespace App\Policies\Grading;

use App\Domain\Security\Enums\Permission;
use App\Models\Grading\EvaluationMethodRequest;
use App\Models\User;

/**
 * Requests are made from the grade entry screen and decided by the section's schedulers.
 */
class EvaluationMethodRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::SectionsManage->value) || $user->can(Permission::GradesView->value);
    }

    public function view(User $user, EvaluationMethodRequest $request): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, EvaluationMethodRequest $request): bool
    {
        return false;
    }

    public function delete(User $user, EvaluationMethodRequest $request): bool
    {
        return false;
    }
}
