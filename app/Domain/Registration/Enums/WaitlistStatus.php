<?php

namespace App\Domain\Registration\Enums;

enum WaitlistStatus: string
{
    case Waiting = 'waiting';
    case Promoted = 'promoted';
    case Removed = 'removed';
    case Expired = 'expired';

    public function label(): string
    {
        return __('registration.waitlist_statuses.'.$this->value);
    }
}
