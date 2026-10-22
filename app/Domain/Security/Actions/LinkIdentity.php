<?php

namespace App\Domain\Security\Actions;

use App\Domain\Security\Services\AuditLogger;
use App\Models\Security\UserIdentity;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

/**
 * Links an external (SSO) account to a user. One external account belongs to one user, and
 * a user has at most one link per provider.
 */
class LinkIdentity
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function handle(User $user, string $provider, string $subject, ?string $email, string $how): UserIdentity
    {
        $existing = UserIdentity::query()->where('provider', $provider)->where('subject', $subject)->first();

        if ($existing !== null && $existing->user_id !== $user->id) {
            throw ValidationException::withMessages(['sso' => __('security.sso.linked_to_another_account')]);
        }

        if ($existing === null && $user->identities()->where('provider', $provider)->exists()) {
            throw ValidationException::withMessages(['sso' => __('security.sso.already_linked')]);
        }

        $identity = $existing ?? UserIdentity::query()->create([
            'user_id' => $user->id,
            'provider' => $provider,
            'subject' => $subject,
            'email' => $email,
            'linked_at' => CarbonImmutable::now(),
        ]);

        if ($existing === null) {
            $this->audit->record('sso.linked', $user, null, ['provider' => $provider, 'email' => $email, 'how' => $how], $user, 'security');
        }

        return $identity;
    }
}
