<?php

namespace App\Domain\Grading\Enums;

enum GpaRounding: string
{
    case Round = 'round';
    case Floor = 'floor';

    public function label(): string
    {
        return __('grading.gpa_roundings.'.$this->value);
    }
}
