<?php

namespace App\Domain\Graduation\Enums;

/**
 * A waiver counts only while approved. Lifted keeps the history of a waiver taken back.
 */
enum WaiverStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Lifted = 'lifted';

    public function label(): string
    {
        return __('graduation.waiver_statuses.'.$this->value);
    }
}
