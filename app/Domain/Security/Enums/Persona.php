<?php

namespace App\Domain\Security\Enums;

/**
 * Identity based roles granted from data, never assigned by hand
 * (porting/porting-plan.md section 7.2).
 */
enum Persona: string
{
    case Applicant = 'applicant';
    case Student = 'student';
    case Alumni = 'alumni';
    case Instructor = 'instructor';
    case Advisor = 'advisor';

    public function label(): string
    {
        return __('security.personas.'.$this->value);
    }
}
