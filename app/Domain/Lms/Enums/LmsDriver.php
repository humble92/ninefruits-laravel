<?php

namespace App\Domain\Lms\Enums;

/**
 * Which connector talks to the platform. Only null (records, no calls) ships in this phase; the others are named so settings can be prepared.
 */
enum LmsDriver: string
{
    case Null = 'null';
    case Moodle = 'moodle';
    case OpenEdx = 'openedx';

    public function label(): string
    {
        return __('lms.drivers.'.$this->value);
    }
}
