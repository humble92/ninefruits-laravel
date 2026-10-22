<?php

namespace App\Actions\Fortify;

use App\Domain\Security\Enums\MfaMethod;
use App\Domain\Security\Services\EmailCodeChallenge;
use App\Domain\Security\Services\MfaPolicy;
use App\Domain\Security\Services\TrustedDevices;
use App\Models\User;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\Request;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\LoginRateLimiter;

/**
 * Fortify's two-factor redirect for any enrolled method (authenticator app or email code),
 * skipped for devices the user chose to remember. Users whose only method is email get a
 * code sent right away.
 */
class RedirectIfTwoFactorChallengeRequired extends RedirectIfTwoFactorAuthenticatable
{
    public function __construct(
        StatefulGuard $guard,
        LoginRateLimiter $limiter,
        private readonly TrustedDevices $trustedDevices,
        private readonly MfaPolicy $policy,
        private readonly EmailCodeChallenge $emailCodes,
    ) {
        parent::__construct($guard, $limiter);
    }

    /**
     * @param  Request  $request
     * @param  callable  $next
     * @return mixed
     */
    public function handle($request, $next)
    {
        $user = $this->validateCredentials($request);

        if (! $user instanceof User || $this->trustedDevices->isTrusted($request, $user)) {
            return $next($request);
        }

        $methods = $this->policy->methodsFor($user);

        if ($methods === []) {
            return $next($request);
        }

        if ($methods === [MfaMethod::EmailCode]) {
            $this->emailCodes->send($user, EmailCodeChallenge::PURPOSE_LOGIN);
        }

        return $this->twoFactorChallengeResponse($request, $user);
    }
}
