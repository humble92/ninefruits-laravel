<?php

namespace App\Domain\Admission\Actions;

use App\Domain\Admission\Enums\ApplicationStatus;
use App\Domain\Security\Enums\Permission;
use App\Domain\Security\Services\AuditLogger;
use App\Models\Admission\Application;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Review steps by admissions staff (applications.review in scope): start the review, then
 * admit or reject. A decision can be changed until it is released to the applicant. Cycles
 * that release decisions immediately notify the applicant at once.
 */
class DecideApplication
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly ReleaseDecisions $release,
    ) {}

    public function startReview(Application $application, User $reviewer): Application
    {
        self::ensureReviewer($application, $reviewer);

        if ($application->status !== ApplicationStatus::Submitted) {
            throw ValidationException::withMessages(['status' => __('admissions.errors.invalid_transition')]);
        }

        $application->forceFill(['status' => ApplicationStatus::UnderReview, 'review_started_at' => CarbonImmutable::now()])->save();
        $this->audit->record('application.review_started', $application, null, causer: $reviewer);

        return $application;
    }

    public function admit(Application $application, User $reviewer, ?string $note = null): Application
    {
        return $this->decide($application, ApplicationStatus::Admitted, $reviewer, $note);
    }

    public function reject(Application $application, User $reviewer, string $note): Application
    {
        return $this->decide($application, ApplicationStatus::Rejected, $reviewer, $note);
    }

    private function decide(Application $application, ApplicationStatus $decision, User $reviewer, ?string $note): Application
    {
        self::ensureReviewer($application, $reviewer);

        $changeable = $application->status->isAwaitingDecision()
            || ($application->status->isDecided() && ! $application->isDecisionVisibleToApplicant());

        if (! $changeable) {
            throw ValidationException::withMessages(['status' => __('admissions.errors.invalid_transition')]);
        }

        DB::transaction(function () use ($application, $decision, $reviewer, $note) {
            $previous = $application->status;
            $application->forceFill([
                'status' => $decision,
                'decided_by_id' => $reviewer->id,
                'decided_at' => CarbonImmutable::now(),
                'decision_note' => $note,
                'review_started_at' => $application->review_started_at ?? CarbonImmutable::now(),
            ])->save();

            $this->audit->record("application.{$decision->value}", $application, $note, ['previous_status' => $previous->value], $reviewer);
        });

        if ($application->cycle->should_release_decisions_immediately) {
            $this->release->handle([$application], $reviewer);
        }

        return $application;
    }

    public static function ensureReviewer(Application $application, User $reviewer): void
    {
        if (! $application->isInScopeOf($reviewer, Permission::ApplicationsReview)) {
            throw new AuthorizationException;
        }
    }
}
