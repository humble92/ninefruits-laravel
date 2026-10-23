<?php

namespace App\Domain\Grading\Data;

/**
 * How a relatively evaluated section's grades spread over the grade groups, against the
 * institution's caps.
 */
final readonly class GradeDistribution
{
    /**
     * @param  array<string, array{count: int, percent: float, cap: int|null, limit: float|null, over: bool}>  $groups  by group letter
     * @param  list<string>  $violations  messages for groups over their cap
     */
    public function __construct(
        public int $graded,
        public int $minimumStudents,
        public bool $checked,
        public array $groups,
        public array $violations,
    ) {}

    public function passes(): bool
    {
        return $this->violations === [];
    }
}
