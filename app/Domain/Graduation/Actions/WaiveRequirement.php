<?php

namespace App\Domain\Graduation\Actions;

use App\Domain\Graduation\Enums\WaiverStatus;
use App\Domain\Security\Enums\Permission;
use App\Domain\Security\Services\AuditLogger;
use App\Models\Curriculum\CurriculumRequirement;
use App\Models\Graduation\DegreeRequirementWaiver;
use App\Models\Person\StudentRecord;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Waiving one requirement for one student (decided 2026-10-23: two people). Staff with
 * graduation.process request it with a reason; someone else with the same permission
 * approves or rejects; an approved waiver can be lifted later with a reason. Every step is
 * audited and the row keeps the history.
 */
class WaiveRequirement
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function request(StudentRecord $record, CurriculumRequirement $requirement, string $reason, User $staff): DegreeRequirementWaiver
    {
        $this->authorize($staff, $record);

        if (blank($reason)) {
            throw ValidationException::withMessages(['reason' => __('registration.errors.reason_required')]);
        }

        try {
            $waiver = DB::transaction(fn () => DegreeRequirementWaiver::query()->create([
                'student_record_id' => $record->id, 'curriculum_requirement_id' => $requirement->id, 'reason' => $reason,
                'requested_by_id' => $staff->id, 'requested_at' => CarbonImmutable::now(),
            ]));
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['requirement' => __('graduation.errors.waiver_exists')]);
        }

        $this->audit->record('graduation.waiver_requested', $waiver, $reason, ['student_number' => $record->student_number, 'requirement_id' => $requirement->id], $staff);

        return $waiver;
    }

    public function approve(DegreeRequirementWaiver $waiver, User $approver, ?string $note = null): DegreeRequirementWaiver
    {
        $this->authorizeDecision($waiver, $approver);
        $waiver->forceFill(['status' => WaiverStatus::Approved, 'decided_by_id' => $approver->id, 'decided_at' => CarbonImmutable::now(), 'decision_note' => $note])->save();
        $this->audit->record('graduation.waiver_approved', $waiver, $note, ['student_record_id' => $waiver->student_record_id, 'requirement_id' => $waiver->curriculum_requirement_id], $approver);

        return $waiver;
    }

    public function reject(DegreeRequirementWaiver $waiver, User $approver, string $note): DegreeRequirementWaiver
    {
        $this->authorizeDecision($waiver, $approver);

        if (blank($note)) {
            throw ValidationException::withMessages(['note' => __('registration.errors.reason_required')]);
        }

        $waiver->forceFill(['status' => WaiverStatus::Rejected, 'decided_by_id' => $approver->id, 'decided_at' => CarbonImmutable::now(), 'decision_note' => $note])->save();
        $this->audit->record('graduation.waiver_rejected', $waiver, $note, ['student_record_id' => $waiver->student_record_id, 'requirement_id' => $waiver->curriculum_requirement_id], $approver);

        return $waiver;
    }

    public function lift(DegreeRequirementWaiver $waiver, User $staff, string $reason): DegreeRequirementWaiver
    {
        $waiver->loadMissing('studentRecord');
        $this->authorize($staff, $waiver->studentRecord);

        if ($waiver->status !== WaiverStatus::Approved) {
            throw ValidationException::withMessages(['status' => __('graduation.errors.waiver_not_approved')]);
        }

        if (blank($reason)) {
            throw ValidationException::withMessages(['reason' => __('registration.errors.reason_required')]);
        }

        $waiver->forceFill(['status' => WaiverStatus::Lifted, 'lifted_by_id' => $staff->id, 'lifted_at' => CarbonImmutable::now(), 'lift_reason' => $reason])->save();
        $this->audit->record('graduation.waiver_lifted', $waiver, $reason, ['student_record_id' => $waiver->student_record_id, 'requirement_id' => $waiver->curriculum_requirement_id], $staff);

        return $waiver;
    }

    private function authorize(User $staff, StudentRecord $record): void
    {
        if (! $staff->can(Permission::GraduationProcess->value) || ! $record->isVisibleTo($staff)) {
            throw new AuthorizationException;
        }
    }

    private function authorizeDecision(DegreeRequirementWaiver $waiver, User $approver): void
    {
        $waiver->loadMissing('studentRecord');
        $this->authorize($approver, $waiver->studentRecord);

        if ($waiver->status !== WaiverStatus::Pending) {
            throw ValidationException::withMessages(['status' => __('grading.errors.request_decided')]);
        }

        if ($waiver->requested_by_id === $approver->id) {
            throw ValidationException::withMessages(['status' => __('graduation.errors.own_waiver')]);
        }
    }
}
