<?php

namespace App\Policies\Security;

use App\Domain\Security\Enums\Permission;
use App\Models\Security\SecurityEvent;
use App\Models\User;

class SecurityEventPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::SecurityEventsView->value);
    }

    public function view(User $user, SecurityEvent $event): bool
    {
        return $user->can(Permission::SecurityEventsView->value);
    }
}
