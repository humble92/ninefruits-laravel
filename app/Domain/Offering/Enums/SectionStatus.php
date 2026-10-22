<?php

namespace App\Domain\Offering\Enums;

/**
 * draft: being planned, visible to staff only. published: in the course catalog (and
 * registration in Phase 5). cancelled: kept for history; its meetings free their rooms
 * and instructors.
 */
enum SectionStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __('offering.section_statuses.'.$this->value);
    }
}
