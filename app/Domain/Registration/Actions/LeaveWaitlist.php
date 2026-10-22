<?php

namespace App\Domain\Registration\Actions;

use App\Domain\Registration\Enums\WaitlistStatus;
use App\Models\Registration\WaitlistEntry;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

class LeaveWaitlist
{
    public function handle(WaitlistEntry $entry, User $actor, ?string $reason = null): WaitlistEntry
    {
        $own = $actor->person_id !== null && $actor->person_id === $entry->studentRecord->person_id;

        if (! $own && ! $entry->section->isRegistrationManageableBy($actor)) {
            throw new AuthorizationException;
        }

        if ($entry->status !== WaitlistStatus::Waiting) {
            throw ValidationException::withMessages(['status' => __('registration.errors.not_waiting')]);
        }

        $entry->forceFill([
            'status' => WaitlistStatus::Removed,
            'resolved_at' => CarbonImmutable::now(),
            'outcome_note' => $own ? __('registration.waitlist.left') : $reason,
        ])->save();

        return $entry;
    }
}
