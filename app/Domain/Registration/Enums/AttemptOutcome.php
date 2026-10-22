<?php

namespace App\Domain\Registration\Enums;

enum AttemptOutcome: string
{
    case Succeeded = 'succeeded';
    case Waitlisted = 'waitlisted';
    case Refused = 'refused';

    public function label(): string
    {
        return __('registration.attempt_outcomes.'.$this->value);
    }
}
