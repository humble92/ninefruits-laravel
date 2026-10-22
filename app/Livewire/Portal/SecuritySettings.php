<?php

namespace App\Livewire\Portal;

use App\Domain\Security\Actions\DisableMfaMethod;
use App\Domain\Security\Actions\EnableEmailMfa;
use App\Domain\Security\Enums\MfaMethod;
use App\Domain\Security\Services\EmailCodeChallenge;
use App\Domain\Security\Services\MfaPolicy;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Two-factor enrollment shared by every user type. The admin panel links here, so a user
 * enrolls once for both the portal and the admin panel.
 */
#[Layout('components.layouts.portal')]
class SecuritySettings extends Component
{
    public string $code = '';

    public string $emailCode = '';

    public bool $emailCodeSent = false;

    public bool $showingRecoveryCodes = false;

    public function enable(EnableTwoFactorAuthentication $enable): mixed
    {
        if ($this->requirePasswordConfirmation()) {
            return null;
        }

        $enable($this->user());

        return null;
    }

    public function confirm(ConfirmTwoFactorAuthentication $confirm): mixed
    {
        if ($this->requirePasswordConfirmation()) {
            return null;
        }

        $this->validate(['code' => ['required', 'string', 'digits:6']]);

        $confirm($this->user(), $this->code);

        $this->reset('code');
        $this->showingRecoveryCodes = true;

        return null;
    }

    public function regenerateRecoveryCodes(GenerateNewRecoveryCodes $generate): mixed
    {
        if ($this->requirePasswordConfirmation()) {
            return null;
        }

        $generate($this->user());
        $this->showingRecoveryCodes = true;

        return null;
    }

    public function disable(DisableMfaMethod $disable): mixed
    {
        return $this->disableMethod($disable, MfaMethod::AuthenticatorApp);
    }

    public function sendEmailCode(EmailCodeChallenge $emailCodes, MfaPolicy $policy): mixed
    {
        if ($this->requirePasswordConfirmation()) {
            return null;
        }

        if (! $policy->emailCodesAllowed()) {
            $this->addError('emailCode', __('security.email_code.not_allowed'));

            return null;
        }

        if (! $emailCodes->send($this->user(), EmailCodeChallenge::PURPOSE_ENROLLMENT)) {
            $this->addError('emailCode', __('security.email_code.wait', ['seconds' => $emailCodes->secondsUntilResend($this->user())]));

            return null;
        }

        $this->emailCodeSent = true;

        return null;
    }

    public function confirmEmail(EnableEmailMfa $enableEmail): mixed
    {
        if ($this->requirePasswordConfirmation()) {
            return null;
        }

        $this->validate(['emailCode' => ['required', 'string', 'digits:6']]);

        $enableEmail->handle($this->user(), $this->emailCode);

        $this->reset('emailCode', 'emailCodeSent');

        return null;
    }

    public function disableEmail(DisableMfaMethod $disable): mixed
    {
        return $this->disableMethod($disable, MfaMethod::EmailCode);
    }

    public function render(MfaPolicy $policy): View
    {
        $user = $this->user();
        $methods = $policy->methodsFor($user);
        $pending = $user->two_factor_secret !== null && $user->two_factor_confirmed_at === null;

        return view('livewire.portal.security-settings', [
            'user' => $user,
            'usesApp' => in_array(MfaMethod::AuthenticatorApp, $methods, true),
            'usesEmail' => in_array(MfaMethod::EmailCode, $methods, true),
            'emailCodesAllowed' => $policy->emailCodesAllowed(),
            'pending' => $pending,
            'requirement' => $policy->requirementFor($user),
            'enrolled' => $methods !== [],
            'qrCodeSvg' => $pending ? $user->twoFactorQrCodeSvg() : null,
            'setupKey' => $pending ? decrypt((string) $user->two_factor_secret) : null,
            'recoveryCodes' => $this->showingRecoveryCodes ? $user->recoveryCodes() : [],
        ])->title(__('security.mfa.title'));
    }

    private function disableMethod(DisableMfaMethod $disable, MfaMethod $method): mixed
    {
        if ($this->requirePasswordConfirmation()) {
            return null;
        }

        $disable->handle($this->user(), $method);

        if ($method === MfaMethod::AuthenticatorApp) {
            $this->showingRecoveryCodes = false;
        }

        return null;
    }

    private function user(): User
    {
        /** @var User */
        return Auth::user();
    }

    /**
     * Redirects to the password confirmation screen when the last confirmation is too old.
     * Returns true when the caller must stop.
     */
    private function requirePasswordConfirmation(): bool
    {
        $confirmedAt = (int) session('auth.password_confirmed_at', 0);

        if (Date::now()->unix() - $confirmedAt < (int) config('auth.password_timeout', 900)) {
            return false;
        }

        session()->put('url.intended', route('portal.security'));
        $this->redirectRoute('password.confirm');

        return true;
    }
}
