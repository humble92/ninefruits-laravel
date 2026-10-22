<?php

namespace App\Domain\Security\Settings;

use App\Domain\Security\Enums\SsoLinkMode;
use App\Domain\Security\Enums\StudentNumberLogin;
use Spatie\LaravelSettings\Settings;

/**
 * Sign-in methods (settings group `authentication`, docs/porting-plan.md section 7.1).
 * The personal email and password always work; the rest is chosen per institution.
 */
class AuthenticationSettings extends Settings
{
    public StudentNumberLogin $student_number_login;

    /** Accounts linked to a school SSO account may still sign in with their password. */
    public bool $password_login_for_sso_users;

    public SsoLinkMode $sso_link_mode;

    /**
     * OIDC providers: list of ['key', 'label', 'issuer', 'client_id', 'client_secret'
     * (encrypted), 'scopes', 'trusts_mfa', 'is_enabled'].
     *
     * spatie/laravel-settings reads @var for casting, so the detailed type is phpstan-only.
     *
     *
     * @phpstan-var list<array<string, mixed>>
     */
    public array $sso_providers;

    /** Failed sign-ins before the account is locked. 0 disables locking. */
    public int $lockout_threshold;

    public int $lockout_minutes;

    public static function group(): string
    {
        return 'authentication';
    }
}
