<?php

namespace App\Domain\Lms\Enums;

enum LinkStatus: string
{
    case Pending = 'pending';
    case Linked = 'linked';
    case Failed = 'failed';

    public function label(): string
    {
        return __('lms.link_statuses.'.$this->value);
    }
}
