<?php

namespace App\Domain\Organization\Enums;

/**
 * How an org unit relates to its successor after a reorganization.
 */
enum OrgUnitChangeType: string
{
    case Renamed = 'renamed';
    case Merged = 'merged';
    case Split = 'split';
    case Moved = 'moved';

    public function label(): string
    {
        return __('reference.org_unit_change_types.'.$this->value);
    }
}
