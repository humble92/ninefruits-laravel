<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Security\Enums\MfaMethod;
use App\Domain\Security\Services\EmailCodeChallenge;
use App\Domain\Security\Services\MfaPolicy;
use App\Models\User;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse;
use Laravel\Fortify\Events\TwoFactorAuthenticationFailed;
use Laravel\Fortify\Events\ValidTwoFactorAuthenticationCodeProvided;
use Laravel\Fortify\Http\Requests\TwoFactorLoginRequest;

/**
 * Second step of sign-in with an emailed one-time code. Uses Fortify's pending login
 * (session login.id), so the password step is the same as for authenticator apps.
 */
class TwoFactorEmailChallengeController
{
    public function __construct(
        private readonly StatefulGuard $guard,
        private readonly MfaPolicy $policy,
        private readonly EmailCodeChallenge $emailCodes,
    ) {}

    public function send(TwoFactorLoginRequest $request): RedirectResponse
    {
        $user = $this->challengedUser($request);

        $sent = $this->emailCodes->send($user, EmailCodeChallenge::PURPOSE_LOGIN);

        return back()->with('status', $sent
            ? __('security.email_code.sent')
            : __('security.email_code.wait', ['seconds' => $this->emailCodes->secondsUntilResend($user)]));
    }

    public function store(TwoFactorLoginRequest $request): mixed
    {
        $user = $this->challengedUser($request);

        $request->validate(['email_code' => ['required', 'string', 'digits:6']]);

        if (! $this->emailCodes->verify($user, EmailCodeChallenge::PURPOSE_LOGIN, (string) $request->input('email_code'))) {
            event(new TwoFactorAuthenticationFailed($user));

            throw ValidationException::withMessages(['email_code' => __('security.email_code.invalid')]);
        }

        event(new ValidTwoFactorAuthenticationCodeProvided($user));

        $this->guard->login($user, $request->remember());

        $request->session()->regenerate();

        return app(TwoFactorLoginResponse::class);
    }

    private function challengedUser(TwoFactorLoginRequest $request): User
    {
        $user = $request->challengedUser();

        if (! $user instanceof User || ! in_array(MfaMethod::EmailCode, $this->policy->methodsFor($user), true)) {
            abort(403);
        }

        return $user;
    }
}
