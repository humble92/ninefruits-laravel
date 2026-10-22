<?php

namespace App\Domain\Security\Services;

use App\Domain\Security\Enums\MfaEnforcement;
use App\Domain\Security\Enums\MfaMethod;
use App\Domain\Security\Enums\MfaRequirement;
use App\Domain\Security\Settings\SecuritySettings;
use App\Models\Security\RoleAssignment;
use App\Models\User;
use Carbon\CarbonImmutable;

/**
 * Applies the two-factor authentication policy from SecuritySettings to a user.
 */
class MfaPolicy
{
    public function __construct(private readonly SecuritySettings $settings) {}

    public function requirementFor(User $user): MfaRequirement
    {
        return match ($this->settings->mfa_enforcement) {
            MfaEnforcement::Disabled => MfaRequirement::None,
            MfaEnforcement::Recommended => MfaRequirement::Recommended,
            MfaEnforcement::RequiredForAll => MfaRequirement::Required,
            MfaEnforcement::RequiredForRoles => $this->holdsRequiredRole($user)
                ? MfaRequirement::Required
                : MfaRequirement::Recommended,
        };
    }

    public function isEnrolled(User $user): bool
    {
        return $this->methodsFor($user) !== [];
    }

    /**
     * Two-factor methods the user can sign in with. Email codes count only while the
     * institution allows them.
     *
     * @return list<MfaMethod>
     */
    public function methodsFor(User $user): array
    {
        $methods = [];

        if ($user->two_factor_secret !== null && $user->two_factor_confirmed_at !== null) {
            $methods[] = MfaMethod::AuthenticatorApp;
        }

        if ($user->email_mfa_enabled_at !== null && $this->emailCodesAllowed()) {
            $methods[] = MfaMethod::EmailCode;
        }

        return $methods;
    }

    public function emailCodesAllowed(): bool
    {
        return $this->settings->mfa_email_code_enabled;
    }

    /**
     * Whether the user must enroll before doing anything else. Starts the grace period the
     * first time enforcement applies to the user.
     */
    public function mustEnrollNow(User $user): bool
    {
        if ($this->requirementFor($user) !== MfaRequirement::Required || $this->isEnrolled($user)) {
            return false;
        }

        $endsAt = $this->graceEndsAt($user);

        return $endsAt === null || $endsAt->isPast();
    }

    public function graceEndsAt(User $user): ?CarbonImmutable
    {
        if ($this->settings->mfa_grace_period_days <= 0) {
            return null;
        }

        if ($user->mfa_grace_started_at === null) {
            $user->forceFill(['mfa_grace_started_at' => CarbonImmutable::now()])->saveQuietly();
        }

        return $user->mfa_grace_started_at->addDays($this->settings->mfa_grace_period_days);
    }

    public function rememberDeviceDays(): int
    {
        return max(0, $this->settings->mfa_remember_device_days);
    }

    private function holdsRequiredRole(User $user): bool
    {
        if ($this->settings->mfa_required_role_ids === []) {
            return false;
        }

        return RoleAssignment::query()
            ->active()
            ->where('user_id', $user->id)
            ->whereIn('role_id', $this->settings->mfa_required_role_ids)
            ->exists();
    }
}
