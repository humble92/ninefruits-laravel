<?php

namespace App\Domain\Security\Services;

use App\Domain\Security\Actions\RecordSecurityEvent;
use App\Domain\Security\Enums\SecurityEventType;
use App\Domain\Security\Notifications\TwoFactorEmailCodeNotification;
use App\Models\User;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Facades\RateLimiter;

/**
 * One-time codes sent by email, for two-factor sign-in and for enrolling the email method.
 *
 * Only a keyed hash of the code is stored (cache), it expires after CODE_LIFETIME_MINUTES,
 * is single use, and is discarded after MAX_ATTEMPTS wrong entries. Sending is limited per
 * user so the feature cannot be used to flood a mailbox.
 */
class EmailCodeChallenge
{
    public const string PURPOSE_LOGIN = 'login';

    public const string PURPOSE_ENROLLMENT = 'enrollment';

    public const int CODE_LIFETIME_MINUTES = 10;

    public const int MAX_ATTEMPTS = 5;

    public const int SENDS_PER_HOUR = 5;

    public const int RESEND_AFTER_SECONDS = 60;

    public function __construct(
        private readonly Cache $cache,
        private readonly RecordSecurityEvent $recordSecurityEvent,
    ) {}

    /**
     * Sends a new code (replacing any earlier one). Returns false when rate limited.
     */
    public function send(User $user, string $purpose): bool
    {
        $minuteKey = "mfa-email-code-resend:{$user->id}";
        $hourKey = "mfa-email-code-hourly:{$user->id}";

        if (RateLimiter::tooManyAttempts($minuteKey, 1) || RateLimiter::tooManyAttempts($hourKey, self::SENDS_PER_HOUR)) {
            return false;
        }

        RateLimiter::hit($minuteKey, self::RESEND_AFTER_SECONDS);
        RateLimiter::hit($hourKey, 3600);

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $this->cache->put($this->key($user, $purpose), [
            'hash' => $this->hash($user, $purpose, $code),
            'attempts' => 0,
        ], now()->addMinutes(self::CODE_LIFETIME_MINUTES));

        $user->notify(new TwoFactorEmailCodeNotification($code, self::CODE_LIFETIME_MINUTES));

        $this->recordSecurityEvent->handle(SecurityEventType::MfaEmailCodeSent, $user, properties: ['purpose' => $purpose]);

        return true;
    }

    public function verify(User $user, string $purpose, string $code): bool
    {
        $key = $this->key($user, $purpose);
        $stored = $this->cache->get($key);

        if (! is_array($stored)) {
            return false;
        }

        if (hash_equals($stored['hash'], $this->hash($user, $purpose, trim($code)))) {
            $this->cache->forget($key);

            return true;
        }

        $attempts = $stored['attempts'] + 1;

        if ($attempts >= self::MAX_ATTEMPTS) {
            $this->cache->forget($key);
        } else {
            $this->cache->put($key, ['hash' => $stored['hash'], 'attempts' => $attempts], now()->addMinutes(self::CODE_LIFETIME_MINUTES));
        }

        return false;
    }

    public function secondsUntilResend(User $user): int
    {
        return RateLimiter::availableIn("mfa-email-code-resend:{$user->id}");
    }

    private function key(User $user, string $purpose): string
    {
        return "mfa-email-code:{$purpose}:{$user->id}";
    }

    private function hash(User $user, string $purpose, string $code): string
    {
        return hash_hmac('sha256', "{$purpose}|{$user->id}|{$code}", (string) config('app.key'));
    }
}
