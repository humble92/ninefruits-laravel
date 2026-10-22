<?php

namespace App\Domain\Registration\Rules;

use App\Domain\Registration\Data\RegistrationContext;
use App\Domain\Registration\Data\RuleCheck;
use App\Domain\Registration\Enums\AttemptAction;
use App\Domain\Registration\Enums\RuleCode;
use App\Domain\Registration\Enums\RuleSeverity;
use App\Models\Registration\Enrollment;

/**
 * One section of a course per term (also a unique index on enrollments).
 */
class DuplicateCourseRule implements RegistrationRule
{
    public function code(): RuleCode
    {
        return RuleCode::DuplicateCourse;
    }

    public function defaults(): array
    {
        return ['severity' => RuleSeverity::Block, 'allows_override' => false, 'parameters' => []];
    }

    public function appliesTo(AttemptAction $action): bool
    {
        return in_array($action, [AttemptAction::Enroll, AttemptAction::Waitlist, AttemptAction::Promote, AttemptAction::Cart], true);
    }

    public function check(RegistrationContext $context, array $parameters): RuleCheck
    {
        $other = $context->termEnrollments()->first(fn (Enrollment $enrollment) => $enrollment->course_id === $context->section->course_id);

        return $other === null
            ? RuleCheck::pass()
            : RuleCheck::fail(__('registration.rules.duplicate_course.failed', ['section' => $other->section->label()]));
    }
}
