<?php

namespace App\Domain\Security\Actions;

use App\Domain\Security\Enums\MfaMethod;
use App\Domain\Security\Enums\MfaRequirement;
use App\Domain\Security\Enums\SecurityEventType;
use App\Domain\Security\Services\MfaPolicy;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;

/**
 * Turns off one two-factor method. When the policy requires two-factor authentication,
 * the user must keep at least one other method.
 */
class DisableMfaMethod
{
    public function __construct(
        private readonly MfaPolicy $policy,
        private readonly DisableTwoFactorAuthentication $disableAuthenticatorApp,
        private readonly RecordSecurityEvent $recordSecurityEvent,
    ) {}

    public function handle(User $user, MfaMethod $method): void
    {
        $remaining = array_values(array_filter($this->policy->methodsFor($user), fn (MfaMethod $enrolled) => $enrolled !== $method));

        if ($remaining === [] && $this->policy->requirementFor($user) === MfaRequirement::Required) {
            throw ValidationException::withMessages(['disable' => __('security.mfa.cannot_disable_required')]);
        }

        match ($method) {
            // Fires TwoFactorAuthenticationDisabled, recorded by RecordAuthenticationEvents.
            MfaMethod::AuthenticatorApp => ($this->disableAuthenticatorApp)($user),
            MfaMethod::EmailCode => $this->disableEmail($user),
        };
    }

    private function disableEmail(User $user): void
    {
        $user->forceFill(['email_mfa_enabled_at' => null])->save();

        $this->recordSecurityEvent->handle(SecurityEventType::MfaEmailDisabled, $user);
    }
}
