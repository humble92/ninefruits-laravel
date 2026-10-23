<?php

namespace App\Domain\Grading\Enums;

/**
 * How a section is graded. Absolute: the instructor enters a score and the grade follows
 * from it (on both scales). Relative: the instructor gives grades only, no score.
 */
enum EvaluationMethod: string
{
    case Absolute = 'absolute';
    case Relative = 'relative';

    public function label(): string
    {
        return __('grading.evaluation_methods.'.$this->value);
    }
}
