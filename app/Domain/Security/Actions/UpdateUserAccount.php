<?php

namespace App\Domain\Security\Actions;

use App\Domain\Security\Enums\SecurityEventType;
use App\Domain\Security\Services\AuditLogger;
use App\Domain\Security\Services\AuthorizationService;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Staff edit of an account (name, email, locale, active flag). Changing the email resets
 * verification, ends the account's sessions and leaves a security event; nobody edits an
 * account more privileged than their own or deactivates themselves (security review
 * 2026-10-23, H4).
 */
class UpdateUserAccount
{
    private const array EDITABLE = ['name', 'email', 'locale', 'is_active'];

    public function __construct(private readonly AuthorizationService $authorization, private readonly RecordSecurityEvent $events, private readonly AuditLogger $audit) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(User $user, array $data, User $actor): User
    {
        $this->guard($user, $data, $actor);

        return DB::transaction(function () use ($user, $data, $actor) {
            $previousEmail = $user->email;
            $user->forceFill(array_intersect_key($data, array_flip(self::EDITABLE)));
            $emailChanged = $user->isDirty('email');

            if ($emailChanged) {
                $user->email_verified_at = null;
            }

            $user->save();

            if ($emailChanged) {
                DB::table('sessions')->where('user_id', $user->id)->delete();
                $this->events->handle(SecurityEventType::EmailChanged, $user, $previousEmail, $user, ['to' => $user->email, 'by' => $actor->id]);
                $this->audit->record('user.email_changed', $user, null, ['from' => $previousEmail, 'to' => $user->email], $actor, 'security');
            }

            return $user;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function guard(User $user, array $data, User $actor): void
    {
        if ($actor->is($user) && array_key_exists('is_active', $data) && ! $data['is_active']) {
            throw ValidationException::withMessages(['is_active' => __('security.errors.cannot_deactivate_self')]);
        }

        if ($actor->is($user)) {
            return;
        }

        $missing = array_values(array_diff($this->authorization->permissionNames($user), $this->authorization->permissionNames($actor)));

        if ($missing !== []) {
            throw ValidationException::withMessages(['email' => __('security.errors.target_more_privileged', ['permissions' => implode(', ', array_slice($missing, 0, 3))])]);
        }
    }
}
