<?php

namespace App\Domain\Registration\Data;

use App\Domain\Registration\Enums\AttemptAction;
use App\Domain\Registration\Enums\RuleCode;
use App\Models\Offering\Section;
use App\Models\Person\StudentRecord;
use App\Models\Registration\Enrollment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * What a registration rule looks at: who, which section, what action, when, and the
 * student's other enrollments in the term.
 */
final class RegistrationContext
{
    /**
     * @param  list<RuleCode>  $waived  Rules staff chose to waive for this action (with a reason)
     * @param  Collection<int, Enrollment>|null  $termEnrollments  Active enrollments in the term (loaded on demand)
     */
    public function __construct(
        public readonly StudentRecord $record,
        public readonly Section $section,
        public readonly AttemptAction $action,
        public readonly CarbonImmutable $at,
        public readonly ?User $actor = null,
        public readonly bool $byStaff = false,
        public readonly array $waived = [],
        private ?Collection $termEnrollments = null,
    ) {}

    /**
     * Active enrollments of the student in the section's term, other than this section.
     *
     * @return Collection<int, Enrollment>
     */
    public function termEnrollments(): Collection
    {
        return $this->termEnrollments ??= Enrollment::query()->active()
            ->where('student_record_id', $this->record->id)
            ->where('term_id', $this->section->term_id)
            ->where('section_id', '!=', $this->section->id)
            ->with(['section.meetings', 'section.course'])
            ->get();
    }
}
