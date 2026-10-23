<?php

namespace App\Domain\Grading\Enums;

enum GradeChangeStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __('grading.change_statuses.'.$this->value);
    }
}
