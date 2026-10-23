<?php

namespace App\Domain\Evaluation\Data;

use App\Models\Evaluation\TermEvaluation;
use App\Models\Offering\Section;

/**
 * A section's evaluation: response counts always; per question results and comments only
 * when shown (enough responses).
 */
final readonly class SectionEvaluationResult
{
    /**
     * @param  array<int, array{count: int, mean: float|null, distribution: array<int, int>}>  $questions  by question id (scale questions)
     * @param  array<int, list<string>>  $comments  by question id (text questions), in random order
     */
    public function __construct(
        public Section $section,
        public ?TermEvaluation $termEvaluation,
        public int $responses,
        public int $expected,
        public int $ratePercent,
        public bool $shown,
        public array $questions,
        public array $comments,
        public ?float $overallMean,
    ) {}
}
