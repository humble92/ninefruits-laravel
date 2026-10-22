<?php

namespace App\Domain\Grading\Enums;

/**
 * Grade entry state of an enrollment (Phase 6): none, draft, submitted, finalized.
 */
enum GradeStatus: string
{
    case None = 'none';
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Finalized = 'finalized';

    public function label(): string
    {
        return __('registration.grade_statuses.'.$this->value);
    }
}
