<?php

namespace App\Domain\Security\Enums;

enum MfaRequirement: string
{
    case None = 'none';
    case Recommended = 'recommended';
    case Required = 'required';
}
