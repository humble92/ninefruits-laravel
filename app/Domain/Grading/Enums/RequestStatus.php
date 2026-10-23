<?php

namespace App\Domain\Grading\Enums;

/**
 * State of a request someone else decides (evaluation method change).
 */
enum RequestStatus: string
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
