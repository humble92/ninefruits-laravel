<?php

namespace App\Domain\Certificate\Enums;

/**
 * Which student records a certificate kind is for.
 */
enum CertificateAudience: string
{
    case Any = 'any';
    case Enrolled = 'enrolled';
    case Graduated = 'graduated';

    public function label(): string
    {
        return __('certificate.audiences.'.$this->value);
    }
}
