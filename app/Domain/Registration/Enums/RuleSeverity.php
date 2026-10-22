<?php

namespace App\Domain\Registration\Enums;

enum RuleSeverity: string
{
    case Block = 'block';
    case Warn = 'warn';

    public function label(): string
    {
        return __('registration.rule_severities.'.$this->value);
    }
}
