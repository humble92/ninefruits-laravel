<?php

namespace App\Domain\Person\Actions;

use App\Domain\Person\Enums\AdvisorRole;
use App\Domain\Security\Services\AuditLogger;
use App\Models\Person\AdvisorAssignment;
use App\Models\Person\Instructor;
use App\Models\Person\StudentRecord;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Assigns an advisor from a date. A new primary advisor ends the current one the day
 * before, so the history shows who advised when.
 */
class AssignAdvisor
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function handle(StudentRecord $record, Instructor $instructor, CarbonImmutable $startDate, AdvisorRole $role = AdvisorRole::Primary, ?User $assignedBy = null): AdvisorAssignment
    {
        if (! $instructor->isCurrent()) {
            throw ValidationException::withMessages(['instructor_id' => __('people.errors.instructor_not_current')]);
        }

        return DB::transaction(function () use ($record, $instructor, $startDate, $role, $assignedBy) {
            if ($role === AdvisorRole::Primary) {
                $record->advisorAssignments()
                    ->where('role', AdvisorRole::Primary)
                    ->whereNull('end_date')
                    ->each(function (AdvisorAssignment $current) use ($startDate) {
                        $current->update(['end_date' => $current->start_date->greaterThanOrEqualTo($startDate) ? $current->start_date : $startDate->subDay()]);
                    });
            }

            $assignment = AdvisorAssignment::query()->create([
                'student_record_id' => $record->id,
                'instructor_id' => $instructor->id,
                'role' => $role,
                'start_date' => $startDate,
            ]);

            $this->audit->record('advisor.assigned', $assignment, null, [
                'student_record_id' => $record->id,
                'instructor_id' => $instructor->id,
                'role' => $role->value,
            ], $assignedBy);

            return $assignment;
        });
    }
}
