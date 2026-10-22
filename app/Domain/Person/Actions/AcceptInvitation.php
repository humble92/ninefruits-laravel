<?php

namespace App\Domain\Person\Actions;

use App\Domain\Security\Services\AuditLogger;
use App\Models\Person\AccountInvitation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Creates the account from an invitation. Receiving the invitation proves the email, so
 * the address is marked verified.
 */
class AcceptInvitation
{
    public function __construct(private readonly AuditLogger $audit) {}

    public static function find(string $token): ?AccountInvitation
    {
        $invitation = AccountInvitation::query()->where('token_hash', hash('sha256', $token))->first();

        return $invitation?->isUsable() ? $invitation : null;
    }

    public function handle(string $token, string $password): User
    {
        $invitation = self::find($token) ?? throw ValidationException::withMessages(['token' => __('people.invitations.invalid')]);

        return DB::transaction(function () use ($invitation, $password) {
            if (User::query()->where('email', $invitation->email)->orWhere('person_id', $invitation->person_id)->exists()) {
                throw ValidationException::withMessages(['token' => __('people.invitations.invalid')]);
            }

            $user = User::query()->create(['email' => $invitation->email, 'password' => $password]);
            $user->forceFill(['person_id' => $invitation->person_id, 'email_verified_at' => CarbonImmutable::now()])->save();

            $invitation->forceFill(['accepted_at' => CarbonImmutable::now()])->save();

            $this->audit->record('account.invitation_accepted', $user, null, ['person_id' => $invitation->person_id], $user);

            return $user;
        });
    }
}
