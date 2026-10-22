<?php

namespace App\Domain\Admission\Actions;

use App\Domain\Admission\Enums\ApplicationStatus;
use App\Domain\Security\Services\AuditLogger;
use App\Models\Admission\Application;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

/**
 * An admitted applicant accepts (confirmed: waiting for matriculation) or declines
 * (withdrawn) a released offer before its deadline. Staff can record a response received
 * by other means, with a reason.
 */
class RespondToOffer
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function handle(Application $application, bool $accept, User $actor, ?string $reason = null): Application
    {
        if (! $application->isOwnedBy($actor)) {
            DecideApplication::ensureReviewer($application, $actor);

            if (blank($reason)) {
                throw ValidationException::withMessages(['reason' => __('admissions.errors.reason_required')]);
            }
        }

        if ($application->status !== ApplicationStatus::Admitted || ! $application->isDecisionVisibleToApplicant()) {
            throw ValidationException::withMessages(['status' => __('admissions.errors.no_open_offer')]);
        }

        if ($application->offer_expires_at !== null && $application->offer_expires_at->isPast() && $application->isOwnedBy($actor)) {
            throw ValidationException::withMessages(['status' => __('admissions.errors.offer_expired')]);
        }

        $now = CarbonImmutable::now();
        $application->forceFill($accept
            ? ['status' => ApplicationStatus::Confirmed, 'responded_at' => $now]
            : ['status' => ApplicationStatus::Withdrawn, 'responded_at' => $now, 'withdrawn_at' => $now, 'withdrawal_reason' => $reason ?? 'declined'])
            ->save();

        $this->audit->record($accept ? 'application.offer_accepted' : 'application.offer_declined', $application, $reason, causer: $actor);

        return $application;
    }
}
