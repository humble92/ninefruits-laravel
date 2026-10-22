<?php

namespace App\Domain\Security\Actions;

use App\Domain\Security\Enums\SecurityEventType;
use App\Domain\Security\Services\EmailCodeChallenge;
use App\Domain\Security\Services\MfaPolicy;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

/**
 * Turns on email codes as a two-factor method after the user proves access to the
 * account email with a code from EmailCodeChallenge (purpose: enrollment).
 */
class EnableEmailMfa
{
    public function __construct(
        private readonly MfaPolicy $policy,
        private readonly EmailCodeChallenge $emailCodes,
        private readonly RecordSecurityEvent $recordSecurityEvent,
    ) {}

    public function handle(User $user, string $code): void
    {
        if (! $this->policy->emailCodesAllowed()) {
            throw ValidationException::withMessages(['email_code' => __('security.email_code.not_allowed')]);
        }

        if (! $this->emailCodes->verify($user, EmailCodeChallenge::PURPOSE_ENROLLMENT, $code)) {
            throw ValidationException::withMessages(['email_code' => __('security.email_code.invalid')]);
        }

        $user->forceFill(['email_mfa_enabled_at' => CarbonImmutable::now()])->save();

        $this->recordSecurityEvent->handle(SecurityEventType::MfaEmailEnabled, $user);
    }
}
