<?php

namespace App\Domain\Person\Enums;

enum EmploymentType: string
{
    case FullTime = 'full_time';
    case PartTime = 'part_time';
    case Adjunct = 'adjunct';
    case Visiting = 'visiting';
    case Emeritus = 'emeritus';

    public function label(): string
    {
        return __('people.employment_types.'.$this->value);
    }
}
