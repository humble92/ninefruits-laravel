<?php

namespace App\Policies\Admission;

use App\Domain\Security\Enums\Permission;
use App\Models\Admission\AdmissionCycle;
use App\Models\User;

class AdmissionCyclePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::AdmissionCyclesManage->value) || $user->can(Permission::ApplicationsView->value) || $user->can(Permission::ApplicationsReview->value);
    }

    public function view(User $user, AdmissionCycle $cycle): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::AdmissionCyclesManage->value);
    }

    public function update(User $user, AdmissionCycle $cycle): bool
    {
        return $user->can(Permission::AdmissionCyclesManage->value);
    }

    /**
     * Only cycles without applications (the database refuses others).
     */
    public function delete(User $user, AdmissionCycle $cycle): bool
    {
        return $user->can(Permission::AdmissionCyclesManage->value);
    }
}
