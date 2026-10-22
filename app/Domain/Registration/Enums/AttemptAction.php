<?php

namespace App\Domain\Registration\Enums;

enum AttemptAction: string
{
    case Enroll = 'enroll';
    case Drop = 'drop';
    case Withdraw = 'withdraw';
    case Waitlist = 'waitlist';
    case Promote = 'promote';
    case Cart = 'cart';

    public function label(): string
    {
        return __('registration.attempt_actions.'.$this->value);
    }
}
