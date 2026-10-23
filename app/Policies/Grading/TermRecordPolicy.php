<?php

namespace App\Policies\Grading;

use App\Domain\Security\Enums\Permission;
use App\Models\Grading\TermRecord;
use App\Models\User;

/**
 * Term records are calculated, never edited by hand.
 */
class TermRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::GradesView->value) || $user->can(Permission::GradesManage->value);
    }

    public function view(User $user, TermRecord $record): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, TermRecord $record): bool
    {
        return false;
    }

    public function delete(User $user, TermRecord $record): bool
    {
        return false;
    }
}
