<?php

namespace App\Domain\Curriculum\Enums;

enum GradingBasis: string
{
    case Graded = 'graded';
    case PassFail = 'pass_fail';

    public function label(): string
    {
        return __('curriculum.grading_bases.'.$this->value);
    }
}
