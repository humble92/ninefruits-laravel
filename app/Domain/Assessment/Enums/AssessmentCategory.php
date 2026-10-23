<?php

namespace App\Domain\Assessment\Enums;

/**
 * Groups kinds for display and filters; the category does not change behaviour.
 */
enum AssessmentCategory: string
{
    case Language = 'language';
    case Comprehensive = 'comprehensive';
    case Thesis = 'thesis';
    case External = 'external';

    public function label(): string
    {
        return __('assessment.categories.'.$this->value);
    }
}
