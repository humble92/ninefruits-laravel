<?php

namespace App\Domain\Lms\Enums;

enum SyncStatus: string
{
    case Queued = 'queued';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case Skipped = 'skipped';

    public function label(): string
    {
        return __('lms.sync_statuses.'.$this->value);
    }
}
