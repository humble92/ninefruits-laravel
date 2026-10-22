<?php

namespace App\Domain\Term\Enums;

/**
 * Operating state of a term. Changed only by administrator actions (ChangeTermStatus),
 * independent of dates and term windows.
 */
enum TermStatus: string
{
    case Planned = 'planned';
    case Published = 'published';
    case GradesClosed = 'grades_closed';
    case Archived = 'archived';

    public function label(): string
    {
        return __('reference.term_statuses.'.$this->value);
    }

    /**
     * @return list<self>
     */
    public function allowedNext(): array
    {
        return match ($this) {
            self::Planned => [self::Published],
            self::Published => [self::Planned, self::GradesClosed],
            self::GradesClosed => [self::Published, self::Archived],
            self::Archived => [],
        };
    }

    public function canMoveTo(self $next): bool
    {
        return in_array($next, $this->allowedNext(), true);
    }
}
