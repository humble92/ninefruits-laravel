<?php

namespace App\Domain\Offering\Enums;

enum InstructorRole: string
{
    case Primary = 'primary';
    case CoInstructor = 'co_instructor';
    case Assistant = 'assistant';

    public function label(): string
    {
        return __('offering.instructor_roles.'.$this->value);
    }
}
