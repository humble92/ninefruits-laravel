<?php

namespace App\Domain\Person\Enums;

enum AdvisorRole: string
{
    case Primary = 'primary';
    case CoAdvisor = 'co_advisor';

    public function label(): string
    {
        return __('people.advisor_roles.'.$this->value);
    }
}
