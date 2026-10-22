<?php

namespace App\Domain\Admission\Enums;

/**
 * draft → submitted → under_review → admitted | rejected; admitted → confirmed (the
 * applicant accepts, then matriculation creates the student record) or withdrawn
 * (declined). Applicants may withdraw at any point before matriculation. Drafts left in
 * a closed cycle become expired.
 */
enum ApplicationStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case Admitted = 'admitted';
    case Rejected = 'rejected';
    case Confirmed = 'confirmed';
    case Withdrawn = 'withdrawn';
    case Expired = 'expired';

    public function label(): string
    {
        return __('admissions.application_statuses.'.$this->value);
    }

    /**
     * Not closed: blocks another application to the same cycle.
     */
    public function isOpen(): bool
    {
        return ! in_array($this, [self::Withdrawn, self::Rejected, self::Expired], true);
    }

    /**
     * Waiting for a staff decision.
     */
    public function isAwaitingDecision(): bool
    {
        return in_array($this, [self::Submitted, self::UnderReview], true);
    }

    public function isDecided(): bool
    {
        return in_array($this, [self::Admitted, self::Rejected], true);
    }

    /**
     * @return list<string>
     */
    public static function openValues(): array
    {
        return array_values(array_map(fn (self $status) => $status->value, array_filter(self::cases(), fn (self $status) => $status->isOpen())));
    }
}
