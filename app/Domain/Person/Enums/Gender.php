<?php

namespace App\Domain\Person\Enums;

enum Gender: string
{
    case Female = 'female';
    case Male = 'male';
    case Other = 'other';
    case Undisclosed = 'undisclosed';

    public function label(): string
    {
        return __('people.genders.'.$this->value);
    }
}
