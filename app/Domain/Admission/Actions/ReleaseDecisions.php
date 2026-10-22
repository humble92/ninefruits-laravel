<?php

namespace App\Domain\Admission\Actions;

use App\Domain\Admission\Enums\ApplicationStatus;
use App\Domain\Admission\Notifications\ApplicationDecisionNotification;
use App\Domain\Security\Enums\Permission;
use App\Domain\Security\Services\AuditLogger;
use App\Models\Admission\Application;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Notification;

/**
 * Makes decisions visible to applicants and emails them. Admitted applicants get an offer
 * deadline when the cycle sets response days. Applications not decided, already released
 * or outside the user's review scope are skipped.
 */
class ReleaseDecisions
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  iterable<Application>  $applications
     * @return int Number released
     */
    public function handle(iterable $applications, User $releasedBy): int
    {
        $released = 0;

        foreach ($applications as $application) {
            if (! $application->status->isDecided() || $application->isDecisionVisibleToApplicant()) {
                continue;
            }

            if (! $application->isInScopeOf($releasedBy, Permission::ApplicationsReview)) {
                continue;
            }

            $now = CarbonImmutable::now();
            $days = $application->cycle->offer_response_days;
            $application->forceFill([
                'decision_released_at' => $now,
                'offer_expires_at' => $application->status === ApplicationStatus::Admitted && $days !== null ? $now->addDays($days) : null,
            ])->save();

            $this->audit->record('application.decision_released', $application, null, ['decision' => $application->status->value], $releasedBy);

            $user = $application->person->user;

            if ($user !== null) {
                $user->notify(new ApplicationDecisionNotification($application));
            } elseif ($application->person->email !== null) {
                Notification::route('mail', $application->person->email)->notify(new ApplicationDecisionNotification($application));
            }

            $released++;
        }

        return $released;
    }
}
