<?php

namespace App\Domain\Assessment\Enums;

enum AssessmentRegistrationStatus: string
{
    case Registered = 'registered';
    case Cancelled = 'cancelled';
    case Absent = 'absent';

    public function label(): string
    {
        return __('assessment.registration_statuses.'.$this->value);
    }
}
