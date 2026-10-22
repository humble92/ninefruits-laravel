<?php

namespace App\Domain\Security\Actions;

use App\Domain\Security\Enums\SecurityEventType;
use App\Domain\Security\Services\AuditLogger;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Administrator reset of a user's two-factor authentication, e.g. after a lost phone.
 * A reason is required and the reset is audited. Remembered devices stop working because
 * their signature includes the enrollment.
 */
class ResetUserMfa
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly RecordSecurityEvent $recordSecurityEvent,
    ) {}

    public function handle(User $user, string $reason, User $resetBy): void
    {
        DB::transaction(function () use ($user, $reason, $resetBy) {
            $user->forceFill([
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed_at' => null,
                'email_mfa_enabled_at' => null,
                'mfa_grace_started_at' => null,
            ])->save();

            $this->audit->record('mfa.reset', $user, $reason, causer: $resetBy, log: 'security');
            $this->recordSecurityEvent->handle(SecurityEventType::MfaReset, $resetBy, subject: $user, properties: ['reason' => $reason]);
        });
    }
}
