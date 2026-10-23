<?php

namespace App\Domain\Assessment\Enums;

enum CorrectionStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __('assessment.correction_statuses.'.$this->value);
    }
}
