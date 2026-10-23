<?php

namespace App\Domain\Grading\Data;

/**
 * Credits and GPA of a set of graded courses. Values are decimal strings as stored.
 */
final readonly class CreditTotals
{
    public function __construct(
        public string $attemptedCredits,
        public string $earnedCredits,
        public string $gpaCredits,
        public string $gradePointsTotal,
        /** Null without GPA credits (only P/F or incomplete courses). */
        public ?string $gpa,
    ) {}
}
