<?php

namespace App\Domain\Security\Actions;

use App\Domain\Security\Services\AuditLogger;
use App\Models\Security\UserIdentity;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class UnlinkIdentity
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function handle(User $user, UserIdentity $identity): void
    {
        if ($identity->user_id !== $user->id) {
            throw new AuthorizationException;
        }

        $identity->delete();

        $this->audit->record('sso.unlinked', $user, null, ['provider' => $identity->provider], $user, 'security');
    }
}
