<?php

namespace App\Policies\Admission;

use App\Domain\Security\Enums\Permission;
use App\Models\Admission\Application;
use App\Models\User;

class ApplicationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ApplicationsView->value) || $user->can(Permission::ApplicationsReview->value);
    }

    public function view(User $user, Application $application): bool
    {
        return $application->isOwnedBy($user)
            || $application->isInScopeOf($user, Permission::ApplicationsView)
            || $application->isInScopeOf($user, Permission::ApplicationsReview);
    }

    /**
     * Applications are created by applicants in the portal.
     */
    public function create(User $user): bool
    {
        return false;
    }

    public function review(User $user, Application $application): bool
    {
        return $application->isInScopeOf($user, Permission::ApplicationsReview);
    }

    public function matriculate(User $user, Application $application): bool
    {
        return $application->isInScopeOf($user, Permission::ApplicationsMatriculate);
    }

    public function update(User $user, Application $application): bool
    {
        return false;
    }

    public function delete(User $user, Application $application): bool
    {
        return false;
    }
}
