<?php

namespace App\Domain\Registration\Rules;

use App\Domain\Registration\Data\RegistrationContext;
use App\Domain\Registration\Data\RuleCheck;
use App\Domain\Registration\Enums\AttemptAction;
use App\Domain\Registration\Enums\RuleCode;
use App\Domain\Registration\Enums\RuleSeverity;

/**
 * The section's include and exclude conditions (legacy TARGET_ST, TARGET_ST_DEPART,
 * TARGET_ST_GRADE: department, admission year, both).
 */
class EligibilityRule implements RegistrationRule
{
    public function code(): RuleCode
    {
        return RuleCode::Eligibility;
    }

    public function defaults(): array
    {
        return ['severity' => RuleSeverity::Block, 'allows_override' => true, 'parameters' => []];
    }

    public function appliesTo(AttemptAction $action): bool
    {
        return in_array($action, [AttemptAction::Enroll, AttemptAction::Waitlist, AttemptAction::Promote, AttemptAction::Cart], true);
    }

    public function check(RegistrationContext $context, array $parameters): RuleCheck
    {
        return $context->section->admits($context->record) ? RuleCheck::pass() : RuleCheck::fail(__('registration.rules.eligibility.failed'));
    }
}
