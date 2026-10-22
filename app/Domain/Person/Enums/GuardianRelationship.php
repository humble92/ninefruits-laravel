<?php

namespace App\Domain\Person\Enums;

enum GuardianRelationship: string
{
    case Father = 'father';
    case Mother = 'mother';
    case Guardian = 'guardian';
    case Spouse = 'spouse';
    case Sibling = 'sibling';
    case Grandparent = 'grandparent';
    case Other = 'other';

    public function label(): string
    {
        return __('people.guardian_relationships.'.$this->value);
    }
}
