<?php

namespace App\Domain\Registration\Enums;

/**
 * Explicit status instead of the legacy "grade is NULL" convention (docs/porting-plan.md
 * section 10.3). Rows are never deleted: a drop keeps the row as dropped.
 */
enum EnrollmentStatus: string
{
    case Enrolled = 'enrolled';
    case Dropped = 'dropped';
    case Withdrawn = 'withdrawn';
    case Completed = 'completed';

    public function label(): string
    {
        return __('registration.enrollment_statuses.'.$this->value);
    }
}
