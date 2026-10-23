<?php

namespace App\Domain\Graduation\Actions;

use App\Domain\Security\Enums\Permission;
use App\Domain\Security\Services\AuditLogger;
use App\Models\Curriculum\CurriculumRequirement;
use App\Models\Graduation\DegreeRequirementWaiver;
use App\Models\Person\StudentRecord;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

/**
 * Waiving one requirement for one student, with a reason; and lifting the waiver.
 */
class WaiveRequirement
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function grant(StudentRecord $record, CurriculumRequirement $requirement, string $reason, User $staff): DegreeRequirementWaiver
    {
        if (! $staff->can(Permission::GraduationProcess->value) || ! $record->isVisibleTo($staff)) {
            throw new AuthorizationException;
        }

        if (blank($reason)) {
            throw ValidationException::withMessages(['reason' => __('registration.errors.reason_required')]);
        }

        $waiver = DegreeRequirementWaiver::query()->updateOrCreate(
            ['student_record_id' => $record->id, 'curriculum_requirement_id' => $requirement->id],
            ['reason' => $reason, 'granted_by_id' => $staff->id, 'granted_at' => CarbonImmutable::now()],
        );
        $this->audit->record('graduation.requirement_waived', $waiver, $reason, ['student_number' => $record->student_number, 'requirement_id' => $requirement->id], $staff);

        return $waiver;
    }

    public function lift(DegreeRequirementWaiver $waiver, User $staff, string $reason): void
    {
        if (! $staff->can(Permission::GraduationProcess->value)) {
            throw new AuthorizationException;
        }

        $this->audit->record('graduation.waiver_lifted', $waiver, $reason, ['student_record_id' => $waiver->student_record_id, 'requirement_id' => $waiver->curriculum_requirement_id], $staff);
        $waiver->delete();
    }
}
