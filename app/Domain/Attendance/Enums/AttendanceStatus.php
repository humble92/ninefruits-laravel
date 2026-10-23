<?php

namespace App\Domain\Attendance\Enums;

/**
 * Excused: an absence the institution accepts (illness, official business); it does not count as absent.
 */
enum AttendanceStatus: string
{
    case Present = 'present';
    case Late = 'late';
    case Absent = 'absent';
    case Excused = 'excused';

    public function label(): string
    {
        return __('attendance.statuses.'.$this->value);
    }
}
