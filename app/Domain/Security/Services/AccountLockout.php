<?php

namespace App\Domain\Security\Services;

use App\Domain\Security\Actions\RecordSecurityEvent;
use App\Domain\Security\Enums\SecurityEventType;
use App\Domain\Security\Settings\AuthenticationSettings;
use App\Models\User;
use Carbon\CarbonImmutable;

/**
 * Locks an account after repeated failed passwords, whatever sign-in name was used (email
 * or student number), in addition to the per IP rate limit. Locked accounts get the same
 * error as a wrong password, so the message reveals nothing about the account.
 */
class AccountLockout
{
    public function __construct(
        private readonly AuthenticationSettings $settings,
        private readonly RecordSecurityEvent $recordSecurityEvent,
    ) {}

    public function recordFailure(User $user): void
    {
        if ($this->settings->lockout_threshold <= 0) {
            return;
        }

        $count = $user->failed_login_count + 1;
        $attributes = ['failed_login_count' => $count];

        if ($count >= $this->settings->lockout_threshold) {
            $attributes['locked_until'] = CarbonImmutable::now()->addMinutes(max(1, $this->settings->lockout_minutes));
            $attributes['failed_login_count'] = 0;
            $this->recordSecurityEvent->handle(SecurityEventType::LoginLockedOut, $user, $user->email, properties: ['minutes' => $this->settings->lockout_minutes]);
        }

        $user->forceFill($attributes)->saveQuietly();
    }

    public function reset(User $user): void
    {
        if ($user->failed_login_count !== 0 || $user->locked_until !== null) {
            $user->forceFill(['failed_login_count' => 0, 'locked_until' => null])->saveQuietly();
        }
    }
}
