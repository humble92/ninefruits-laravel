<?php

namespace App\Domain\Curriculum\Enums;

/**
 * Prerequisite: completed before (or with allows_concurrent, in the same term).
 * Corequisite: taken in the same term unless already completed.
 */
enum RequisiteKind: string
{
    case Prerequisite = 'prerequisite';
    case Corequisite = 'corequisite';

    public function label(): string
    {
        return __('curriculum.requisite_kinds.'.$this->value);
    }
}
