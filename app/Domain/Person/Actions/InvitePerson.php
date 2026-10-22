<?php

namespace App\Domain\Person\Actions;

use App\Domain\Person\Notifications\AccountInvitationNotification;
use App\Domain\Person\Settings\StudentRecordSettings;
use App\Domain\Security\Services\AuditLogger;
use App\Models\Person\AccountInvitation;
use App\Models\Person\Person;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Sends an invitation to create an account for a person who has none (e.g. students
 * imported from CSV). Earlier open invitations of the person stop working.
 */
class InvitePerson
{
    public function __construct(
        private readonly StudentRecordSettings $settings,
        private readonly AuditLogger $audit,
    ) {}

    public function handle(Person $person, string $email, ?User $invitedBy = null): AccountInvitation
    {
        $email = Str::lower(trim($email));

        if ($person->user()->exists()) {
            throw ValidationException::withMessages(['email' => __('people.errors.already_has_account')]);
        }

        if (User::query()->where('email', $email)->exists()) {
            throw ValidationException::withMessages(['email' => __('people.errors.email_in_use')]);
        }

        $token = Str::random(48);
        $expiresAt = CarbonImmutable::now()->addDays($this->settings->invitation_valid_days);

        $invitation = DB::transaction(function () use ($person, $email, $token, $expiresAt, $invitedBy) {
            $person->invitations()->whereNull('accepted_at')->update(['expires_at' => CarbonImmutable::now()]);

            $invitation = AccountInvitation::query()->create([
                'person_id' => $person->id,
                'email' => $email,
                'token_hash' => hash('sha256', $token),
                'expires_at' => $expiresAt,
                'invited_by_id' => $invitedBy?->id,
            ]);

            $this->audit->record('account.invited', $person, null, ['email' => $email], $invitedBy);

            return $invitation;
        });

        Notification::route('mail', $email)->notify(new AccountInvitationNotification(route('invitations.show', $token), $expiresAt));

        return $invitation;
    }
}
