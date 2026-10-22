<?php

namespace App\Http\Middleware;

use App\Domain\Security\Enums\MfaRequirement;
use App\Domain\Security\Services\MfaPolicy;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the two-factor policy on every authenticated request, so a policy change takes
 * effect immediately. Registered as Livewire persistent middleware so component updates
 * are checked against the original page route too.
 */
class EnforceMfaPolicy
{
    /**
     * Routes a user who must enroll can still reach.
     *
     * @var list<string>
     */
    public const array ALLOWED_ROUTES = [
        'portal.security',
        'two-factor.*',
        'password.confirm*',
        'logout',
        'locale.update',
    ];

    public const string REMINDER_SESSION_KEY = 'mfa.reminder';

    public function __construct(private readonly MfaPolicy $policy) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return $next($request);
        }

        $requirement = $this->policy->requirementFor($user);

        if ($requirement === MfaRequirement::None || $this->policy->isEnrolled($user)) {
            return $next($request);
        }

        if ($this->policy->mustEnrollNow($user)) {
            if ($request->routeIs(...self::ALLOWED_ROUTES)) {
                return $next($request);
            }

            if ($request->expectsJson()) {
                abort(403, __('security.mfa.enrollment_required'));
            }

            return redirect()->route('portal.security')->with('status', __('security.mfa.enrollment_required'));
        }

        if ($request->hasSession()) {
            $request->session()->now(self::REMINDER_SESSION_KEY, [
                'required' => $requirement === MfaRequirement::Required,
                'deadline' => $requirement === MfaRequirement::Required ? $this->policy->graceEndsAt($user)?->toIso8601String() : null,
            ]);
        }

        return $next($request);
    }
}
