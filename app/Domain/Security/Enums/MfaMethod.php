<?php

namespace App\Domain\Security\Enums;

enum MfaMethod: string
{
    case AuthenticatorApp = 'authenticator_app';
    case EmailCode = 'email_code';

    public function label(): string
    {
        return __('security.mfa_methods.'.$this->value);
    }
}
