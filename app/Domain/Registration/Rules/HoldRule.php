<?php

namespace App\Domain\Registration\Rules;

use App\Domain\Registration\Data\RegistrationContext;
use App\Domain\Registration\Data\RuleCheck;
use App\Domain\Registration\Enums\AttemptAction;
use App\Domain\Registration\Enums\RuleCode;
use App\Domain\Registration\Enums\RuleSeverity;
use App\Models\Registration\RegistrationHold;

/**
 * Holds that block registration (legacy SRDENY). The message names the hold type, never
 * the internal reason.
 */
class HoldRule implements RegistrationRule
{
    public function code(): RuleCode
    {
        return RuleCode::Hold;
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
        $hold = RegistrationHold::query()->where('student_record_id', $context->record->id)
            ->where('blocks_registration', true)->inForce($context->at)->first();

        return $hold === null
            ? RuleCheck::pass()
            : RuleCheck::fail(__('registration.rules.hold.failed', ['type' => $hold->hold_type->label()]), ['hold_id' => $hold->id]);
    }
}
