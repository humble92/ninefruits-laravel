<?php

namespace App\Domain\Curriculum\Enums;

use App\Domain\Person\Enums\AdditionalProgramRole;

/**
 * How a student follows a program: as the primary program of the record, or as a second
 * major or minor. Requirements differ per role.
 */
enum ProgramRole: string
{
    case Primary = 'primary';
    case SecondMajor = 'second_major';
    case Minor = 'minor';

    public function label(): string
    {
        return __('curriculum.program_roles.'.$this->value);
    }

    public static function fromAdditional(AdditionalProgramRole $role): self
    {
        return self::from($role->value);
    }
}
