<?php

namespace App\Domain\Security\Enums;

enum MfaEnforcement: string
{
    case Disabled = 'disabled';
    case Recommended = 'recommended';
    case RequiredForRoles = 'required_for_roles';
    case RequiredForAll = 'required_for_all';

    public function label(): string
    {
        return __('security.mfa_enforcement.'.$this->value);
    }
}
