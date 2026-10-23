<?php

namespace App\Domain\Attendance\Enums;

/**
 * Cancelled meetings (holidays, instructor absence) are kept but excluded from the attendance totals.
 */
enum ClassMeetingStatus: string
{
    case Scheduled = 'scheduled';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __('attendance.meeting_statuses.'.$this->value);
    }
}
