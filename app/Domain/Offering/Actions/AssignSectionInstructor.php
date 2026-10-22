<?php

namespace App\Domain\Offering\Actions;

use App\Domain\Offering\Enums\InstructorRole;
use App\Domain\Offering\Services\ScheduleConflicts;
use App\Models\Offering\Section;
use App\Models\Offering\SectionInstructor;
use App\Models\Person\Instructor;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

/**
 * Assigns an instructor to a section. Several instructors can share a section; an
 * instructor already teaching at one of its meeting times is refused by the database.
 */
class AssignSectionInstructor
{
    public function __construct(private readonly ScheduleConflicts $conflicts) {}

    public function handle(Section $section, Instructor $instructor, InstructorRole $role, User $user): SectionInstructor
    {
        if (! $section->isManageableBy($user)) {
            throw new AuthorizationException;
        }

        if (! $instructor->isCurrent($section->session->start_date)) {
            throw ValidationException::withMessages(['instructor_id' => __('offering.errors.instructor_not_current')]);
        }

        if ($section->instructorAssignments()->where('instructor_id', $instructor->id)->exists()) {
            throw ValidationException::withMessages(['instructor_id' => __('offering.errors.instructor_already_assigned')]);
        }

        return $this->conflicts->guard(fn () => SectionInstructor::query()->create([
            'section_id' => $section->id,
            'instructor_id' => $instructor->id,
            'role' => $role,
        ]), 'instructor_id');
    }
}
