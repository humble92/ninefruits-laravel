<?php

namespace App\Domain\Registration\Rules;

use App\Domain\Registration\Data\RegistrationContext;
use App\Domain\Registration\Data\RuleCheck;
use App\Domain\Registration\Enums\AttemptAction;
use App\Domain\Registration\Enums\RuleCode;
use App\Domain\Registration\Enums\RuleSeverity;
use App\Domain\Registration\Services\CourseHistory;
use App\Models\Registration\Enrollment;

/**
 * Taking a course again (it or an equivalent was completed before on this record) is a
 * retake (legacy SSC_RAPPLY marking, now automatic). Parameters: allowed (false: no
 * retakes), max_attempts (total including this one), below_grade (only when the best
 * earlier grade is below this code). Courses that may be repeated for credit are not
 * retakes. Passes with a note when it is a retake; the enrollment is marked.
 */
class RepeatCourseRule implements RegistrationRule
{
    public function __construct(private readonly CourseHistory $history) {}

    public function code(): RuleCode
    {
        return RuleCode::RepeatCourse;
    }

    public function defaults(): array
    {
        return ['severity' => RuleSeverity::Block, 'allows_override' => true, 'parameters' => ['allowed' => true, 'max_attempts' => null, 'below_grade' => null]];
    }

    public function appliesTo(AttemptAction $action): bool
    {
        return in_array($action, [AttemptAction::Enroll, AttemptAction::Waitlist, AttemptAction::Promote, AttemptAction::Cart], true);
    }

    public function check(RegistrationContext $context, array $parameters): RuleCheck
    {
        if ($context->section->version->is_repeatable) {
            return RuleCheck::pass();
        }

        $accepted = $this->history->acceptedCourseIds($context->section->course_id, $context->at);
        $earlier = $this->history->earlierAttempts($context->record, $accepted, $context->section->term_id);

        if ($earlier->isEmpty()) {
            return RuleCheck::pass();
        }

        $details = ['repeats_enrollment_id' => $earlier->firstOrFail()->id];

        if (! ($parameters['allowed'] ?? true)) {
            return RuleCheck::fail(__('registration.rules.repeat_course.not_allowed'), $details);
        }

        $max = $parameters['max_attempts'] ?? null;

        if ($max !== null && $earlier->count() + 1 > (int) $max) {
            return RuleCheck::fail(__('registration.rules.repeat_course.too_many', ['max' => $max]), $details);
        }

        $below = $parameters['below_grade'] ?? null;

        if ($below !== null && $earlier->contains(fn (Enrollment $enrollment) => $enrollment->is_passed && $this->history->meetsMinimum($enrollment->grade_code, (string) $below))) {
            return RuleCheck::fail(__('registration.rules.repeat_course.grade_too_high', ['grade' => $below]), $details);
        }

        return RuleCheck::pass(__('registration.rules.repeat_course.retake'), $details);
    }
}
