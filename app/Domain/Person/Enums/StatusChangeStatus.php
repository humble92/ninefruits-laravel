<?php

namespace App\Domain\Person\Enums;

enum StatusChangeStatus: string
{
    case Requested = 'requested';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __('people.status_change_statuses.'.$this->value);
    }
}
