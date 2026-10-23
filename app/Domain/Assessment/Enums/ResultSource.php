<?php

namespace App\Domain\Assessment\Enums;

/**
 * Session: sat at one of ours. External: an outside body, recorded with evidence.
 */
enum ResultSource: string
{
    case Session = 'session';
    case External = 'external';

    public function label(): string
    {
        return __('assessment.sources.'.$this->value);
    }
}
