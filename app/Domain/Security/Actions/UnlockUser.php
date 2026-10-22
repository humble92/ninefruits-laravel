<?php

namespace App\Domain\Security\Actions;

use App\Domain\Security\Enums\SecurityEventType;
use App\Domain\Security\Services\AuditLogger;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Administrator release of an account locked after failed passwords, before the lock ends
 * by itself. A reason is required.
 */
class UnlockUser
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly RecordSecurityEvent $recordSecurityEvent,
    ) {}

    public function handle(User $user, string $reason, ?User $unlockedBy): void
    {
        DB::transaction(function () use ($user, $reason, $unlockedBy) {
            $user->forceFill(['failed_login_count' => 0, 'locked_until' => null])->save();

            $this->audit->record('account.unlocked', $user, $reason, causer: $unlockedBy, log: 'security');
            $this->recordSecurityEvent->handle(SecurityEventType::AccountUnlocked, $unlockedBy, subject: $user, properties: ['reason' => $reason]);
        });
    }
}
