<?php

namespace App\Domain\Grading\Enums;

/**
 * Which courses a grade on a scale is for (course version grading basis).
 */
enum GradeAppliesTo: string
{
    case Graded = 'graded';
    case PassFail = 'pass_fail';
    case Both = 'both';

    public function label(): string
    {
        return __('grading.applies_to.'.$this->value);
    }
}
