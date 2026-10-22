<?php

namespace App\Domain\Security\Enums;

/**
 * Whether a student number can be used instead of the email to sign in (authentication settings).
 */
enum StudentNumberLogin: string
{
    case Disabled = 'disabled';
    case ActiveRecords = 'active_records';
    case AllRecords = 'all_records';

    public function label(): string
    {
        return __('security.student_number_login.'.$this->value);
    }
}
