<?php

namespace App\Domain\Security\Listeners;

use App\Domain\Security\Actions\RecordSecurityEvent;
use App\Domain\Security\Enums\SecurityEventType;
use App\Domain\Security\Services\TrustedDevices;
use App\Models\User;
use App\Support\Database\DatabaseSessionContext;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Context;
use Laravel\Fortify\Events\RecoveryCodeReplaced;
use Laravel\Fortify\Events\RecoveryCodesGenerated;
use Laravel\Fortify\Events\TwoFactorAuthenticationConfirmed;
use Laravel\Fortify\Events\TwoFactorAuthenticationDisabled;
use Laravel\Fortify\Events\TwoFactorAuthenticationEnabled;
use Laravel\Fortify\Events\TwoFactorAuthenticationFailed;
use Laravel\Fortify\Events\ValidTwoFactorAuthenticationCodeProvided;
use Laravel\Fortify\Fortify;

/**
 * Security log for authentication and two-factor events (docs/porting-plan.md section 8).
 */
class RecordAuthenticationEvents
{
    public function __construct(
        private readonly RecordSecurityEvent $record,
        private readonly TrustedDevices $trustedDevices,
        private readonly DatabaseSessionContext $databaseContext,
    ) {}

    public function handleLogin(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        // The request context was set before sign-in; attribute the rest of the request to the user.
        Context::add('user_id', $event->user->id);
        $this->databaseContext->forUser($event->user->id, Context::get('request_id'));

        $event->user->forceFill(['last_login_at' => CarbonImmutable::now()])->saveQuietly();

        $this->record->handle(SecurityEventType::LoginSucceeded, $event->user, $event->user->email);
    }

    public function handleFailed(Failed $event): void
    {
        $this->record->handle(
            SecurityEventType::LoginFailed,
            $event->user instanceof User ? $event->user : null,
            $this->identifier($event->credentials),
        );
    }

    public function handleLockout(Lockout $event): void
    {
        $this->record->handle(SecurityEventType::LoginLockedOut, identifier: (string) $event->request->input(Fortify::username()));
    }

    public function handleLogout(Logout $event): void
    {
        if ($event->user instanceof User) {
            $this->record->handle(SecurityEventType::Logout, $event->user);
        }
    }

    public function handlePasswordReset(PasswordReset $event): void
    {
        if ($event->user instanceof User) {
            $this->record->handle(SecurityEventType::PasswordReset, $event->user);
        }
    }

    public function handleTwoFactorEnabled(TwoFactorAuthenticationEnabled $event): void
    {
        $this->record->handle(SecurityEventType::MfaEnabled, $event->user);
    }

    public function handleTwoFactorConfirmed(TwoFactorAuthenticationConfirmed $event): void
    {
        $this->record->handle(SecurityEventType::MfaConfirmed, $event->user);
    }

    public function handleTwoFactorDisabled(TwoFactorAuthenticationDisabled $event): void
    {
        $this->trustedDevices->forget();
        $this->record->handle(SecurityEventType::MfaDisabled, $event->user);
    }

    public function handleTwoFactorFailed(TwoFactorAuthenticationFailed $event): void
    {
        $this->record->handle(SecurityEventType::MfaChallengeFailed, $event->user);
    }

    public function handleRecoveryCodeReplaced(RecoveryCodeReplaced $event): void
    {
        $this->record->handle(SecurityEventType::MfaRecoveryCodeUsed, $event->user instanceof User ? $event->user : null);
    }

    public function handleRecoveryCodesGenerated(RecoveryCodesGenerated $event): void
    {
        $this->record->handle(SecurityEventType::MfaRecoveryCodesRegenerated, $event->user);
    }

    public function handleValidTwoFactorCode(ValidTwoFactorAuthenticationCodeProvided $event): void
    {
        if (request()->boolean('remember_device')) {
            $this->trustedDevices->remember($event->user);
        }
    }

    /**
     * @return array<class-string, string>
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            Login::class => 'handleLogin',
            Failed::class => 'handleFailed',
            Lockout::class => 'handleLockout',
            Logout::class => 'handleLogout',
            PasswordReset::class => 'handlePasswordReset',
            TwoFactorAuthenticationEnabled::class => 'handleTwoFactorEnabled',
            TwoFactorAuthenticationConfirmed::class => 'handleTwoFactorConfirmed',
            TwoFactorAuthenticationDisabled::class => 'handleTwoFactorDisabled',
            TwoFactorAuthenticationFailed::class => 'handleTwoFactorFailed',
            RecoveryCodeReplaced::class => 'handleRecoveryCodeReplaced',
            RecoveryCodesGenerated::class => 'handleRecoveryCodesGenerated',
            ValidTwoFactorAuthenticationCodeProvided::class => 'handleValidTwoFactorCode',
        ];
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    private function identifier(array $credentials): ?string
    {
        $value = $credentials[Fortify::username()] ?? null;

        return is_string($value) ? mb_substr($value, 0, 255) : null;
    }
}
