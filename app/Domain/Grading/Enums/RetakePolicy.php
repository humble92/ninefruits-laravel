<?php

namespace App\Domain\Grading\Enums;

/**
 * Which attempt of a retaken course counts in the cumulative GPA. Term GPAs always keep
 * every grade of their term.
 */
enum RetakePolicy: string
{
    case Latest = 'latest';
    case Best = 'best';

    public function label(): string
    {
        return __('grading.retake_policies.'.$this->value);
    }
}
