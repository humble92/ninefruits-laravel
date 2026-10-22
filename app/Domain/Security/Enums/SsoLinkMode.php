<?php

namespace App\Domain\Security\Enums;

/**
 * How a school account is linked on its first SSO sign-in.
 */
enum SsoLinkMode: string
{
    case Manual = 'manual';
    case InstitutionalEmail = 'institutional_email';

    public function label(): string
    {
        return __('security.sso_link_modes.'.$this->value);
    }
}
