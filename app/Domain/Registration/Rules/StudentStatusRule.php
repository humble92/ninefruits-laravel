<?php

namespace App\Domain\Registration\Rules;

use App\Domain\Person\Enums\StudentStatus;
use App\Domain\Registration\Data\RegistrationContext;
use App\Domain\Registration\Data\RuleCheck;
use App\Domain\Registration\Enums\AttemptAction;
use App\Domain\Registration\Enums\RuleCode;
use App\Domain\Registration\Enums\RuleSeverity;

/**
 * Only records in the allowed statuses register (default: enrolled; on leave cannot).
 */
class StudentStatusRule implements RegistrationRule
{
    public function code(): RuleCode
    {
        return RuleCode::StudentStatus;
    }

    public function defaults(): array
    {
        return ['severity' => RuleSeverity::Block, 'allows_override' => true, 'parameters' => ['statuses' => [StudentStatus::Enrolled->value]]];
    }

    public function appliesTo(AttemptAction $action): bool
    {
        return in_array($action, [AttemptAction::Enroll, AttemptAction::Waitlist, AttemptAction::Promote, AttemptAction::Cart], true);
    }

    public function check(RegistrationContext $context, array $parameters): RuleCheck
    {
        $allowed = (array) ($parameters['statuses'] ?? [StudentStatus::Enrolled->value]);

        return in_array($context->record->status->value, $allowed, true)
            ? RuleCheck::pass()
            : RuleCheck::fail(__('registration.rules.student_status.failed', ['status' => $context->record->status->label()]));
    }
}
