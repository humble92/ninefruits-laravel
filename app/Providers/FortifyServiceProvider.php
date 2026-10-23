<?php

namespace App\Providers;

use App\Actions\Fortify\RedirectIfTwoFactorChallengeRequired;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Domain\Admission\Actions\RegisterApplicant;
use App\Domain\Admission\Settings\AdmissionSettings;
use App\Domain\Security\Enums\MfaMethod;
use App\Domain\Security\Services\AccountLockout;
use App\Domain\Security\Services\LoginIdentifierResolver;
use App\Domain\Security\Services\MfaPolicy;
use App\Domain\Security\Settings\AuthenticationSettings;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Actions\AttemptToAuthenticate;
use Laravel\Fortify\Actions\CanonicalizeUsername;
use Laravel\Fortify\Actions\EnsureLoginIsNotThrottled;
use Laravel\Fortify\Actions\PrepareAuthenticatedSession;
use Laravel\Fortify\Fortify;

/**
 * Shared login for the portal and the admin panel (porting/decisions/0001-shared-authentication.md).
 */
class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        Fortify::createUsersUsing(RegisterApplicant::class);
        Fortify::registerView(function () {
            abort_unless(app(AdmissionSettings::class)->applicant_registration_enabled, 404);

            return view('auth.register');
        });
        Fortify::verifyEmailView(fn () => view('auth.verify-email'));

        Fortify::loginView(fn () => view('auth.login'));
        Fortify::twoFactorChallengeView(function (Request $request) {
            $user = User::query()->find($request->session()->get('login.id'));

            return view('auth.two-factor-challenge', [
                'methods' => $user instanceof User ? app(MfaPolicy::class)->methodsFor($user) : [MfaMethod::AuthenticatorApp],
            ]);
        });
        Fortify::requestPasswordResetLinkView(fn () => view('auth.forgot-password'));
        Fortify::resetPasswordView(fn (Request $request) => view('auth.reset-password', ['request' => $request]));
        Fortify::confirmPasswordView(fn () => view('auth.confirm-password'));

        // Email or student number (institution setting). Deactivated, locked and SSO-only
        // accounts get the same failure as a wrong password.
        Fortify::authenticateUsing(function (Request $request): ?User {
            $user = app(LoginIdentifierResolver::class)->resolve((string) $request->input(Fortify::username()));

            if ($user === null || ! $user->is_active || $user->isLocked()) {
                return null;
            }

            if (! Hash::check((string) $request->input('password'), $user->password)) {
                app(AccountLockout::class)->recordFailure($user);

                return null;
            }

            if (! app(AuthenticationSettings::class)->password_login_for_sso_users && $user->identities()->exists()) {
                return null;
            }

            return $user;
        });

        Fortify::authenticateThrough(fn (Request $request) => array_filter([
            config('fortify.limiters.login') ? null : EnsureLoginIsNotThrottled::class,
            config('fortify.lowercase_usernames') ? CanonicalizeUsername::class : null,
            RedirectIfTwoFactorChallengeRequired::class,
            AttemptToAuthenticate::class,
            PrepareAuthenticatedSession::class,
        ]));

        // Keyed by account when the sign-in name resolves to one, so email and student
        // number share one limit.
        RateLimiter::for('login', function (Request $request) {
            $identifier = (string) $request->input(Fortify::username());
            $account = app(LoginIdentifierResolver::class)->resolve($identifier);

            return Limit::perMinute(5)->by(($account !== null ? 'user:'.$account->id : Str::transliterate(Str::lower($identifier))).'|'.$request->ip());
        });

        RateLimiter::for('sso', fn (Request $request) => Limit::perMinute(20)->by($request->ip()));

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }
}
