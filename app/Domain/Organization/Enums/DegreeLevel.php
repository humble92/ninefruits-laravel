<?php

namespace App\Domain\Organization\Enums;

enum DegreeLevel: string
{
    case Associate = 'associate';
    case Bachelor = 'bachelor';
    case Master = 'master';
    case Doctorate = 'doctorate';
    case Certificate = 'certificate';
    case NonDegree = 'non_degree';

    public function label(): string
    {
        return __('reference.degree_levels.'.$this->value);
    }
}
