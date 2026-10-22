<?php

namespace App\Domain\Security\Settings;

use App\Domain\Security\Enums\MfaEnforcement;
use Spatie\LaravelSettings\Settings;

/**
 * Security policy managed by the system administrator (settings group `security`).
 * Read on every request, so changes apply immediately.
 */
class SecuritySettings extends Settings
{
    public MfaEnforcement $mfa_enforcement;

    /** @var list<int> Roles whose members must use two-factor authentication (RequiredForRoles). */
    public array $mfa_required_role_ids;

    /** Days a user may keep working after enforcement first applies to them. */
    public int $mfa_grace_period_days;

    /** Days a device is remembered after a successful challenge. 0 disables. */
    public int $mfa_remember_device_days;

    /**
     * Whether users may use one-time codes sent by email as their two-factor method.
     * Turning it off stops email codes from counting as two-factor authentication.
     */
    public bool $mfa_email_code_enabled;

    public static function group(): string
    {
        return 'security';
    }
}
