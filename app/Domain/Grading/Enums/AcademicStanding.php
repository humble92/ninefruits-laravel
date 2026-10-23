<?php

namespace App\Domain\Grading\Enums;

/**
 * Standing decided at term close (legacy 학사경고 reports). Dismissal is only flagged for
 * review; the status change itself is a student status change with its own approval.
 */
enum AcademicStanding: string
{
    case Good = 'good';
    case Warning = 'warning';
    case DismissalReview = 'dismissal_review';

    public function label(): string
    {
        return __('grading.standings.'.$this->value);
    }
}
