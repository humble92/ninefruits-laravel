<?php

namespace App\Domain\Person\Enums;

/**
 * Programs a student record follows besides its primary program (double major, minor).
 */
enum AdditionalProgramRole: string
{
    case SecondMajor = 'second_major';
    case Minor = 'minor';

    public function label(): string
    {
        return __('people.additional_program_roles.'.$this->value);
    }
}
