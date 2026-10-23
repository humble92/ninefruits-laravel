<?php

namespace App\Domain\Graduation\Data;

use App\Domain\Curriculum\Enums\RequirementKind;

/**
 * One requirement of the set against what the student achieved.
 */
final readonly class RequirementResult
{
    /**
     * @param  list<array{code: string, title: string, credits: string, grade: string|null}>  $courses  the enrollments counted
     * @param  list<string>  $missing  codes of required courses not passed
     */
    public function __construct(
        public int $requirementId,
        public RequirementKind $kind,
        public string $name,
        public ?float $requiredCredits,
        public ?int $requiredCourses,
        public float $achievedCredits,
        public int $achievedCourses,
        public bool $isSatisfied,
        public bool $isWaived,
        public array $courses = [],
        public array $missing = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'requirement_id' => $this->requirementId, 'kind' => $this->kind->value, 'name' => $this->name,
            'required_credits' => $this->requiredCredits, 'required_courses' => $this->requiredCourses,
            'achieved_credits' => $this->achievedCredits, 'achieved_courses' => $this->achievedCourses,
            'satisfied' => $this->isSatisfied, 'waived' => $this->isWaived, 'courses' => $this->courses, 'missing' => $this->missing,
        ];
    }
}
