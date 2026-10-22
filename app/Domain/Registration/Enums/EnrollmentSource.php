<?php

namespace App\Domain\Registration\Enums;

enum EnrollmentSource: string
{
    case Student = 'student';
    case Staff = 'staff';
    case Waitlist = 'waitlist';
    case Import = 'import';

    public function label(): string
    {
        return __('registration.enrollment_sources.'.$this->value);
    }
}
