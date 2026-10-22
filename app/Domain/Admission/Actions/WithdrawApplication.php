<?php

namespace App\Domain\Admission\Actions;

use App\Domain\Admission\Enums\ApplicationStatus;
use App\Domain\Security\Services\AuditLogger;
use App\Models\Admission\Application;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

/**
 * The applicant gives up an open application at any point before matriculation.
 */
class WithdrawApplication
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function handle(Application $application, User $applicant, ?string $reason = null): Application
    {
        if (! $application->isOwnedBy($applicant)) {
            throw new AuthorizationException;
        }

        if (! $application->applicantStatus()->isOpen() || $application->student_record_id !== null) {
            throw ValidationException::withMessages(['status' => __('admissions.errors.invalid_transition')]);
        }

        $now = CarbonImmutable::now();
        $application->forceFill(['status' => ApplicationStatus::Withdrawn, 'withdrawn_at' => $now, 'withdrawal_reason' => $reason])->save();
        $this->audit->record('application.withdrawn', $application, $reason, causer: $applicant);

        return $application;
    }
}
