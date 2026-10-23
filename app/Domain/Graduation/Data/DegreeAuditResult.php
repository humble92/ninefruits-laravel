<?php

namespace App\Domain\Graduation\Data;

use App\Domain\Curriculum\Enums\ProgramRole;
use App\Models\Curriculum\CurriculumRequirementSet;
use App\Models\Organization\Program;

/**
 * The outcome of a degree audit for one program of the student.
 */
final readonly class DegreeAuditResult
{
    /**
     * @param  list<RequirementResult>  $requirements
     * @param  list<string>  $issues  why it is not satisfied, in words (translation keys resolved)
     */
    public function __construct(
        public Program $program,
        public ProgramRole $role,
        public ?CurriculumRequirementSet $set,
        public float $earnedCredits,
        public ?float $requiredCredits,
        public ?float $cumulativeGpa,
        public ?float $minimumGpa,
        public array $requirements,
        public array $issues,
    ) {}

    public function isSatisfied(): bool
    {
        return $this->issues === [];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'program_id' => $this->program->id, 'program_code' => $this->program->code, 'role' => $this->role->value, 'set_id' => $this->set?->id,
            'earned_credits' => $this->earnedCredits, 'required_credits' => $this->requiredCredits, 'cumulative_gpa' => $this->cumulativeGpa, 'minimum_gpa' => $this->minimumGpa,
            'satisfied' => $this->isSatisfied(), 'issues' => $this->issues,
            'requirements' => array_map(fn (RequirementResult $requirement) => $requirement->toArray(), $this->requirements),
        ];
    }
}
