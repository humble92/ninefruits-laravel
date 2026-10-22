<?php

namespace App\Domain\Registration\Rules;

use App\Domain\Registration\Data\RegistrationContext;
use App\Domain\Registration\Data\RuleCheck;
use App\Domain\Registration\Enums\AttemptAction;
use App\Domain\Registration\Enums\RuleCode;
use App\Domain\Registration\Enums\RuleSeverity;
use App\Models\Offering\SectionMeeting;

/**
 * Meetings must not overlap the student's other sections (legacy timetable check).
 * Parameter allowed_overlap_minutes tolerates a short overlap (e.g. 10-minute breaks).
 */
class TimeConflictRule implements RegistrationRule
{
    public function code(): RuleCode
    {
        return RuleCode::TimeConflict;
    }

    public function defaults(): array
    {
        return ['severity' => RuleSeverity::Block, 'allows_override' => true, 'parameters' => ['allowed_overlap_minutes' => 0]];
    }

    public function appliesTo(AttemptAction $action): bool
    {
        return in_array($action, [AttemptAction::Enroll, AttemptAction::Waitlist, AttemptAction::Promote, AttemptAction::Cart], true);
    }

    public function check(RegistrationContext $context, array $parameters): RuleCheck
    {
        $allowed = max(0, (int) ($parameters['allowed_overlap_minutes'] ?? 0));
        $meetings = $context->section->meetings()->where('is_active', true)->get();

        foreach ($context->termEnrollments() as $enrollment) {
            foreach ($enrollment->section->meetings as $other) {
                foreach ($meetings as $meeting) {
                    if (self::overlapMinutes($meeting, $other) > $allowed) {
                        return RuleCheck::fail(__('registration.rules.time_conflict.failed', ['section' => $enrollment->section->label()]), ['conflicts_with' => $enrollment->section_id]);
                    }
                }
            }
        }

        return RuleCheck::pass();
    }

    public static function overlapMinutes(SectionMeeting $a, SectionMeeting $b): int
    {
        if ($a->weekday !== $b->weekday || ! $a->is_active || ! $b->is_active
            || $a->end_date->lessThan($b->start_date) || $b->end_date->lessThan($a->start_date)) {
            return 0;
        }

        return max(0, min($a->endMinute(), $b->endMinute()) - max($a->startMinute(), $b->startMinute()));
    }
}
