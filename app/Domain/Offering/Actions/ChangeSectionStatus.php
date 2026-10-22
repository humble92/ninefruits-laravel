<?php

namespace App\Domain\Offering\Actions;

use App\Domain\Offering\Enums\SectionStatus;
use App\Domain\Offering\Services\ScheduleConflicts;
use App\Domain\Security\Services\AuditLogger;
use App\Models\Offering\Section;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

/**
 * Publishes a section (visible in the course catalog), cancels it (reason required; its
 * meetings free their rooms and instructors) or takes it back to draft. Reopening a
 * cancelled section checks its rooms and instructors again.
 */
class ChangeSectionStatus
{
    public function __construct(
        private readonly ScheduleConflicts $conflicts,
        private readonly AuditLogger $audit,
    ) {}

    public function handle(Section $section, SectionStatus $status, User $user, ?string $reason = null): Section
    {
        if (! $section->isManageableBy($user)) {
            throw new AuthorizationException;
        }

        if ($section->status === $status) {
            return $section;
        }

        if ($status === SectionStatus::Cancelled && blank($reason)) {
            throw ValidationException::withMessages(['reason' => __('offering.errors.reason_required')]);
        }

        $previous = $section->status;
        $this->conflicts->guard(fn () => $section->forceFill(['status' => $status])->save(), 'status');
        $this->audit->record('section.'.$status->value, $section, $reason, ['previous_status' => $previous->value], $user);

        return $section;
    }
}
