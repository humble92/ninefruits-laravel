<?php

namespace App\Policies\Registration;

use App\Domain\Security\Enums\Permission;
use App\Models\Registration\Enrollment;
use App\Models\User;

class EnrollmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::EnrollmentsView->value) || $user->can(Permission::RegistrationManage->value);
    }

    /**
     * Staff registrations go through the registration actions (rules, seats, reasons).
     */
    public function create(User $user): bool
    {
        return $user->can(Permission::RegistrationManage->value);
    }

    public function update(User $user, Enrollment $enrollment): bool
    {
        return $enrollment->section->isRegistrationManageableBy($user);
    }

    public function delete(User $user, Enrollment $enrollment): bool
    {
        return false;
    }
}
