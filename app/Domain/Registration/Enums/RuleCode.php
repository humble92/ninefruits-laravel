<?php

namespace App\Domain\Registration\Enums;

/**
 * Registration rules (porting/porting-plan.md section 10.6). The catalog is code; each term's
 * rule set says per rule: enabled, block or warn, override allowed, parameters.
 */
enum RuleCode: string
{
    /** An open registration or add/drop window for the student (always on). */
    case Window = 'window';
    /** The section is published (always on). */
    case SectionOpen = 'section_open';
    case Hold = 'hold';
    case StudentStatus = 'student_status';
    case Eligibility = 'eligibility';
    case DuplicateCourse = 'duplicate_course';
    case TimeConflict = 'time_conflict';
    case CreditLimit = 'credit_limit';
    case Requisites = 'requisites';
    case RepeatCourse = 'repeat_course';
    /** A seat is free (not a configurable rule: seats are taken atomically, reported here). */
    case Capacity = 'capacity';

    public function label(): string
    {
        return __('registration.rules.'.$this->value.'.label');
    }

    public function description(): string
    {
        return __('registration.rules.'.$this->value.'.description');
    }

    /**
     * Rules a rule set cannot switch off or soften (staff can still waive them).
     */
    public function isCore(): bool
    {
        return in_array($this, [self::Window, self::SectionOpen, self::Capacity], true);
    }
}
