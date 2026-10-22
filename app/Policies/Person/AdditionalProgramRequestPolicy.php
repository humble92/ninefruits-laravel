<?php

namespace App\Policies\Person;

use App\Domain\Security\Enums\Permission;
use App\Models\Person\AdditionalProgramRequest;
use App\Models\User;

class AdditionalProgramRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::AdditionalProgramsApprove->value) || $user->can(Permission::StudentRecordsView->value)
            || $user->can(Permission::StudentRecordsManage->value);
    }

    public function decide(User $user, AdditionalProgramRequest $request): bool
    {
        return $request->isDecidableBy($user);
    }

    public function create(User $user): bool
    {
        return false;
    }
}
