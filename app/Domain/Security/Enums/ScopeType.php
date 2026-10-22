<?php

namespace App\Domain\Security\Enums;

enum ScopeType: string
{
    case Global = 'global';
    case OrgUnit = 'org_unit';
    case Program = 'program';

    public function label(): string
    {
        return __('security.scope_types.'.$this->value);
    }
}
