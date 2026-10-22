<?php

namespace App\Domain\Person\Enums;

/**
 * Status of a student record (legacy SRALTERDEF / SR_ABSENT / SR_LEFT, and the move from
 * SRREGISTRY to SGREGISTRY on graduation). Changed only through status changes.
 */
enum StudentStatus: string
{
    case Enrolled = 'enrolled';
    case OnLeave = 'on_leave';
    case Withdrawn = 'withdrawn';
    case Dismissed = 'dismissed';
    case Graduated = 'graduated';

    public function label(): string
    {
        return __('people.student_statuses.'.$this->value);
    }

    /**
     * The record is current: the student is enrolled or on leave.
     */
    public function isActive(): bool
    {
        return in_array($this, [self::Enrolled, self::OnLeave], true);
    }

    /**
     * @return list<self>
     */
    public static function active(): array
    {
        return [self::Enrolled, self::OnLeave];
    }
}
