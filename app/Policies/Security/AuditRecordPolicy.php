<?php

namespace App\Policies\Security;

use App\Domain\Security\Enums\Permission;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Activity log and record changes. Audit records are read only.
 */
class AuditRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::AuditRecordsView->value);
    }

    public function view(User $user, Model $record): bool
    {
        return $user->can(Permission::AuditRecordsView->value);
    }
}
