<?php

namespace App\Domain\Security\Services;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Cookie\QueueingFactory as CookieJar;
use Illuminate\Http\Request;

/**
 * "Remember this device" for two-factor authentication. The cookie is encrypted by the
 * EncryptCookies middleware and bound to the user's current two-factor enrollment, so
 * resetting or re-enrolling invalidates every remembered device.
 */
class TrustedDevices
{
    public const string COOKIE = 'mfa_trusted_device';

    public function __construct(
        private readonly MfaPolicy $policy,
        private readonly CookieJar $cookies,
    ) {}

    public function remember(User $user): void
    {
        $days = $this->policy->rememberDeviceDays();

        if ($days === 0) {
            return;
        }

        $expiresAt = CarbonImmutable::now()->addDays($days);

        $this->cookies->queue(
            self::COOKIE,
            (string) json_encode(['user_id' => $user->id, 'expires_at' => $expiresAt->timestamp, 'signature' => $this->signature($user)]),
            $days * 24 * 60,
        );
    }

    public function isTrusted(Request $request, User $user): bool
    {
        if ($this->policy->rememberDeviceDays() === 0) {
            return false;
        }

        $payload = json_decode((string) $request->cookie(self::COOKIE), true);

        if (! is_array($payload) || ($payload['user_id'] ?? null) !== $user->id) {
            return false;
        }

        $maxExpiry = CarbonImmutable::now()->addDays($this->policy->rememberDeviceDays())->timestamp;

        return is_int($payload['expires_at'] ?? null)
            && $payload['expires_at'] > CarbonImmutable::now()->timestamp
            && $payload['expires_at'] <= $maxExpiry
            && hash_equals($this->signature($user), (string) ($payload['signature'] ?? ''));
    }

    public function forget(): void
    {
        $this->cookies->expire(self::COOKIE);
    }

    private function signature(User $user): string
    {
        return hash_hmac(
            'sha256',
            $user->id.'|'.$user->two_factor_confirmed_at?->getTimestamp().'|'.$user->two_factor_secret.'|'.$user->email_mfa_enabled_at?->getTimestamp(),
            (string) config('app.key'),
        );
    }
}
