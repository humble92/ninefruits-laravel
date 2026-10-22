<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Security\Actions\LinkIdentity;
use App\Domain\Security\Actions\RecordSecurityEvent;
use App\Domain\Security\Enums\MfaMethod;
use App\Domain\Security\Enums\SecurityEventType;
use App\Domain\Security\Enums\SsoLinkMode;
use App\Domain\Security\Services\EmailCodeChallenge;
use App\Domain\Security\Services\MfaPolicy;
use App\Domain\Security\Services\Sso\OidcClient;
use App\Domain\Security\Services\Sso\OidcException;
use App\Domain\Security\Settings\AuthenticationSettings;
use App\Models\Security\UserIdentity;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Sign-in and account linking with a school OIDC provider (docs/porting-plan.md 7.1).
 *
 * The two-factor policy applies to SSO sign-ins too: unless the provider is trusted for
 * two-factor, enrolled users get the usual challenge (Fortify's pending login).
 */
class SsoController
{
    public function __construct(
        private readonly OidcClient $oidc,
        private readonly StatefulGuard $guard,
    ) {}

    public function redirect(Request $request, string $provider): RedirectResponse
    {
        $config = $this->oidc->provider($provider) ?? abort(404);
        $intent = $request->user() !== null && $request->boolean('link') ? 'link' : 'login';

        // Linking changes how the account can be entered: require a recent password confirmation.
        if ($intent === 'link' && now()->unix() - (int) $request->session()->get('auth.password_confirmed_at', 0) > (int) config('auth.password_timeout', 900)) {
            $request->session()->put('url.intended', route('portal.security'));

            return redirect()->route('password.confirm');
        }

        $state = Str::random(40);
        $nonce = Str::random(40);
        $verifier = Str::random(64);

        $request->session()->put("sso.{$state}", [
            'provider' => $config->key,
            'nonce' => $nonce,
            'verifier' => $verifier,
            'intent' => $intent,
            'user_id' => $request->user()?->getAuthIdentifier(),
        ]);

        return redirect()->away($this->oidc->authorizationUrl($config, $state, $nonce, $verifier));
    }

    public function callback(Request $request, string $provider, LinkIdentity $linkIdentity, MfaPolicy $mfa, EmailCodeChallenge $emailCodes, AuthenticationSettings $settings): RedirectResponse
    {
        $config = $this->oidc->provider($provider) ?? abort(404);
        $attempt = $request->session()->pull('sso.'.$request->query('state'));

        if (! is_array($attempt) || $attempt['provider'] !== $config->key) {
            return $this->fail(__('security.sso.expired'));
        }

        if (! is_string($request->query('code'))) {
            return $this->fail(__('security.sso.cancelled'));
        }

        try {
            $claims = $this->oidc->exchange($config, (string) $request->query('code'), $attempt['verifier'], $attempt['nonce']);
        } catch (OidcException|RequestException $exception) {
            report($exception);

            return $this->fail(__('security.sso.failed'));
        }

        $subject = (string) $claims['sub'];
        $email = isset($claims['email']) && is_string($claims['email']) ? Str::lower($claims['email']) : null;
        $emailVerified = ($claims['email_verified'] ?? false) === true || ($claims['email_verified'] ?? null) === 'true';

        if ($attempt['intent'] === 'link') {
            $user = User::query()->find($attempt['user_id']);

            if ($user === null || ! $request->user()?->is($user)) {
                return $this->fail(__('security.sso.expired'));
            }

            try {
                $linkIdentity->handle($user, $config->key, $subject, $email, 'manual');
            } catch (ValidationException $exception) {
                return redirect()->route('portal.security')->withErrors($exception->errors());
            }

            return redirect()->route('portal.security')->with('status', __('security.sso.linked', ['provider' => $config->label]));
        }

        $identity = UserIdentity::query()->where('provider', $config->key)->where('subject', $subject)->first();
        $user = $identity?->user()->first();

        if ($user === null && $settings->sso_link_mode === SsoLinkMode::InstitutionalEmail && $email !== null && $emailVerified) {
            $userId = DB::scalar('SELECT util.user_id_for_institutional_email(?)', [$email]);
            $user = $userId === null ? null : User::query()->find($userId);

            if ($user !== null) {
                $identity = $linkIdentity->handle($user, $config->key, $subject, $email, 'institutional_email');
            }
        }

        if ($user === null) {
            return $this->fail(__('security.sso.not_linked', ['provider' => $config->label]));
        }

        if (! $user->is_active || $user->isLocked()) {
            app(RecordSecurityEvent::class)->handle(SecurityEventType::LoginFailed, $user, "sso:{$config->key}");

            return $this->fail(__('auth.failed'));
        }

        $identity?->forceFill(['last_used_at' => CarbonImmutable::now()])->save();

        $methods = $mfa->methodsFor($user);

        if (! $config->trustsMfa && $methods !== []) {
            // Same pending login as Fortify's password step; the challenge screen finishes it.
            $request->session()->put(['login.id' => $user->getKey(), 'login.remember' => false]);

            if ($methods === [MfaMethod::EmailCode]) {
                $emailCodes->send($user, EmailCodeChallenge::PURPOSE_LOGIN);
            }

            return redirect()->route('two-factor.login');
        }

        $this->guard->login($user);
        $request->session()->regenerate();

        return redirect()->intended(config('fortify.home'));
    }

    private function fail(string $message): RedirectResponse
    {
        return redirect()->route('login')->withErrors(['email' => $message]);
    }
}
