<?php

namespace App\Domain\Person\Enums;

enum AdditionalProgramRequestStatus: string
{
    case Requested = 'requested';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __('people.additional_program_request_statuses.'.$this->value);
    }
}
