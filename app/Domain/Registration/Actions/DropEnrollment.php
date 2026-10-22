<?php

namespace App\Domain\Registration\Actions;

use App\Domain\Registration\Data\RegistrationContext;
use App\Domain\Registration\Enums\AttemptAction;
use App\Domain\Registration\Enums\AttemptOutcome;
use App\Domain\Registration\Enums\EnrollmentStatus;
use App\Domain\Registration\Jobs\PromoteWaitlistJob;
use App\Domain\Registration\Services\AttemptLog;
use App\Domain\Registration\Services\RegistrationRuleEngine;
use App\Domain\Registration\Services\SeatAllocator;
use App\Domain\Security\Services\AuditLogger;
use App\Models\Registration\Enrollment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Drops (cancels) a registration while registration or add/drop is open for the student,
 * or withdraws it (W) during the session's withdrawal window. Rows are kept with their
 * status. Staff may do either at any time with a reason. The freed seat goes to the
 * waitlist.
 */
class DropEnrollment
{
    public function __construct(
        private readonly RegistrationRuleEngine $engine,
        private readonly SeatAllocator $seats,
        private readonly AttemptLog $attempts,
        private readonly AuditLogger $audit,
    ) {}

    public function drop(Enrollment $enrollment, User $actor, ?string $reason = null): Enrollment
    {
        return $this->end($enrollment, $actor, $reason, AttemptAction::Drop, EnrollmentStatus::Dropped);
    }

    public function withdraw(Enrollment $enrollment, User $actor, ?string $reason = null): Enrollment
    {
        return $this->end($enrollment, $actor, $reason, AttemptAction::Withdraw, EnrollmentStatus::Withdrawn);
    }

    private function end(Enrollment $enrollment, User $actor, ?string $reason, AttemptAction $action, EnrollmentStatus $status): Enrollment
    {
        if ($enrollment->status !== EnrollmentStatus::Enrolled) {
            throw ValidationException::withMessages(['status' => __('registration.errors.not_enrolled')]);
        }

        $record = $enrollment->studentRecord;
        $section = $enrollment->section;
        $byStaff = $actor->person_id === null || $actor->person_id !== $record->person_id;

        if ($byStaff && ! $section->isRegistrationManageableBy($actor)) {
            throw new AuthorizationException;
        }

        if ($byStaff && blank($reason)) {
            throw ValidationException::withMessages(['reason' => __('registration.errors.reason_required')]);
        }

        $evaluation = $this->engine->evaluate(new RegistrationContext($record, $section, $action, CarbonImmutable::now(), $actor, $byStaff));

        if ($evaluation->isBlocked()) {
            $this->attempts->record($record, $section, $action, AttemptOutcome::Refused, $evaluation, $actor);

            throw ValidationException::withMessages(['enrollment' => $evaluation->blockingMessages()]);
        }

        DB::transaction(function () use ($enrollment, $section, $actor, $status) {
            $locked = $this->seats->lock($section->id);
            $now = CarbonImmutable::now();
            $enrollment->forceFill([
                'status' => $status,
                $status === EnrollmentStatus::Dropped ? 'dropped_at' : 'withdrawn_at' => $now,
                'changed_by_id' => $actor->id,
            ])->save();
            $this->seats->release($locked, $enrollment);
        });

        $this->attempts->record($record, $section, $action, AttemptOutcome::Succeeded, $evaluation, $actor);

        if ($byStaff) {
            $this->audit->record('enrollment.staff_'.$status->value, $enrollment, $reason, causer: $actor);
        }

        PromoteWaitlistJob::dispatch($section->id)->afterCommit();

        return $enrollment;
    }
}
