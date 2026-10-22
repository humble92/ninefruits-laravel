<?php

namespace App\Domain\Registration\Enums;

/**
 * Kinds of holds (legacy SRDENY had one kind). "unregistered": the term fee is not paid;
 * there is no tuition module, so staff enter these by hand or CSV (section 4.2).
 */
enum HoldType: string
{
    case Unregistered = 'unregistered';
    case Financial = 'financial';
    case Academic = 'academic';
    case Administrative = 'administrative';
    case Other = 'other';

    public function label(): string
    {
        return __('registration.hold_types.'.$this->value);
    }
}
