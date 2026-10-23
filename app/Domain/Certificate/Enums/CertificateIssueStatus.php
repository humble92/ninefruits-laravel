<?php

namespace App\Domain\Certificate\Enums;

enum CertificateIssueStatus: string
{
    case Issued = 'issued';
    case Revoked = 'revoked';

    public function label(): string
    {
        return __('certificate.statuses.'.$this->value);
    }
}
