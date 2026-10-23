<?php

namespace App\Domain\Assessment\Actions;

use App\Domain\Assessment\Enums\AssessmentRegistrationStatus;
use App\Domain\Security\Enums\Permission;
use App\Domain\Security\Services\AuditLogger;
use App\Models\Assessment\AssessmentRegistration;
use App\Models\Assessment\AssessmentSession;
use App\Models\Person\StudentRecord;
use App\Models\User;
use App\Support\Database\DatabaseSessionContext;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Registering a student for a session: by the student while the window is open and seats
 * remain, or by staff with assessments.record at any time before the session (legacy
 * cet_add*: no window, no capacity, duplicates refused by a unique key). Cancelled
 * registrations stay and can be reactivated.
 */
class RegisterForAssessment
{
    public function __construct(private readonly AuditLogger $audit, private readonly DatabaseSessionContext $context) {}

    public function register(AssessmentSession $session, StudentRecord $record, User $actor, bool $byStaff = false): AssessmentRegistration
    {
        $session->loadMissing('type');

        if ($byStaff) {
            if (! $actor->can(Permission::AssessmentsRecord->value) || ! $record->isVisibleTo($actor)) {
                throw new AuthorizationException;
            }
        } elseif ($record->person_id !== $actor->person_id) {
            throw new AuthorizationException;
        }

        if (! $session->type->is_active) {
            throw ValidationException::withMessages(['session' => __('assessment.errors.type_inactive')]);
        }

        if (! $record->status->isActive()) {
            throw ValidationException::withMessages(['session' => __('assessment.errors.record_inactive')]);
        }

        if (! $byStaff && ! $session->isOpenForRegistration()) {
            throw ValidationException::withMessages(['session' => __('assessment.errors.registration_closed')]);
        }

        // Nobody registers after the session date; a result needs a registration made before it.
        if ($session->held_date->lt(CarbonImmutable::today())) {
            throw ValidationException::withMessages(['session' => __('assessment.errors.session_past')]);
        }

        $existing = AssessmentRegistration::query()->where('assessment_session_id', $session->id)->where('student_record_id', $record->id)->first();

        if ($existing !== null && $existing->status !== AssessmentRegistrationStatus::Cancelled) {
            throw ValidationException::withMessages(['session' => __('assessment.errors.already_registered')]);
        }

        if ($session->capacity !== null && AssessmentRegistration::query()->where('assessment_session_id', $session->id)->active()->count() >= $session->capacity) {
            throw ValidationException::withMessages(['session' => __('assessment.errors.full')]);
        }

        $write = function () use ($existing, $session, $record, $actor, $byStaff) {
            if ($existing !== null) {
                $existing->forceFill(['status' => AssessmentRegistrationStatus::Registered, 'registered_at' => CarbonImmutable::now(), 'registered_by_id' => $byStaff ? $actor->id : null, 'cancelled_at' => null, 'cancel_reason' => null])->save();

                return $existing;
            }

            return AssessmentRegistration::query()->create([
                'assessment_session_id' => $session->id, 'student_record_id' => $record->id, 'registered_at' => CarbonImmutable::now(), 'registered_by_id' => $byStaff ? $actor->id : null,
            ]);
        };

        try {
            // Students have no write policy on registrations: the checks above stand in for it.
            $registration = $byStaff ? DB::transaction($write) : $this->context->asSystem('assessment:register', fn () => DB::transaction($write));
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['session' => __('assessment.errors.already_registered')]);
        }

        $this->audit->record('assessment.registered', $registration, null, ['session_id' => $session->id, 'student_number' => $record->student_number, 'by_staff' => $byStaff], $actor);

        return $registration;
    }

    public function cancel(AssessmentRegistration $registration, User $actor, ?string $reason = null): AssessmentRegistration
    {
        $registration->loadMissing(['session', 'studentRecord']);
        $byStaff = $actor->can(Permission::AssessmentsRecord->value) && $registration->studentRecord->isVisibleTo($actor);

        if (! $byStaff && $registration->studentRecord->person_id !== $actor->person_id) {
            throw new AuthorizationException;
        }

        if ($registration->status === AssessmentRegistrationStatus::Cancelled) {
            throw ValidationException::withMessages(['registration' => __('assessment.errors.already_cancelled')]);
        }

        if (! $byStaff && ! $registration->session->isOpenForRegistration()) {
            throw ValidationException::withMessages(['registration' => __('assessment.errors.registration_closed')]);
        }

        if ($registration->result()->exists()) {
            throw ValidationException::withMessages(['registration' => __('assessment.errors.has_result')]);
        }

        $write = fn () => $registration->forceFill(['status' => AssessmentRegistrationStatus::Cancelled, 'cancelled_at' => CarbonImmutable::now(), 'cancel_reason' => $reason])->save();
        $byStaff ? $write() : $this->context->asSystem('assessment:cancel', $write);
        $this->audit->record('assessment.registration_cancelled', $registration, $reason, ['session_id' => $registration->assessment_session_id], $actor);

        return $registration;
    }

    public function markAbsent(AssessmentRegistration $registration, User $staff): AssessmentRegistration
    {
        if (! $staff->can(Permission::AssessmentsRecord->value)) {
            throw new AuthorizationException;
        }

        if ($registration->status !== AssessmentRegistrationStatus::Registered) {
            throw ValidationException::withMessages(['registration' => __('assessment.errors.not_registered')]);
        }

        $registration->forceFill(['status' => AssessmentRegistrationStatus::Absent])->save();
        $this->audit->record('assessment.absent', $registration, null, ['session_id' => $registration->assessment_session_id], $staff);

        return $registration;
    }
}
