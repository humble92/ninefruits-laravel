<?php

namespace App\Domain\Grading\Data;

use App\Models\Grading\GradeScaleItem;

/**
 * A grade as entered: the grade on the institution's scale, the score it came from
 * (absolute evaluation), and the same score's grade on the comparison scale.
 */
final readonly class GradeChoice
{
    public function __construct(
        public ?GradeScaleItem $item,
        public ?string $score,
        public ?GradeScaleItem $comparison = null,
    ) {}

    public function isEmpty(): bool
    {
        return $this->item === null && $this->score === null;
    }

    /**
     * The enrollment columns for this grade.
     *
     * @return array<string, mixed>
     */
    public function attributes(): array
    {
        return [
            'grade_scale_id' => $this->item?->grade_scale_id,
            'grade_code' => $this->item?->code,
            'score' => $this->score,
            'grade_points' => $this->item?->grade_points,
            'counts_in_gpa' => $this->item?->counts_in_gpa,
            'is_passed' => $this->item?->is_passing,
            'comparison_grade_scale_id' => $this->comparison?->grade_scale_id,
            'comparison_grade_code' => $this->comparison?->code,
            'comparison_grade_points' => $this->comparison?->grade_points,
        ];
    }
}
