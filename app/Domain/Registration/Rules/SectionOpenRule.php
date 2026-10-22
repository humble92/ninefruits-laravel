<?php

namespace App\Domain\Registration\Rules;

use App\Domain\Offering\Enums\SectionStatus;
use App\Domain\Registration\Data\RegistrationContext;
use App\Domain\Registration\Data\RuleCheck;
use App\Domain\Registration\Enums\AttemptAction;
use App\Domain\Registration\Enums\RuleCode;
use App\Domain\Registration\Enums\RuleSeverity;

class SectionOpenRule implements RegistrationRule
{
    public function code(): RuleCode
    {
        return RuleCode::SectionOpen;
    }

    public function defaults(): array
    {
        return ['severity' => RuleSeverity::Block, 'allows_override' => false, 'parameters' => []];
    }

    public function appliesTo(AttemptAction $action): bool
    {
        return ! in_array($action, [AttemptAction::Drop, AttemptAction::Withdraw], true);
    }

    public function check(RegistrationContext $context, array $parameters): RuleCheck
    {
        return $context->section->status === SectionStatus::Published ? RuleCheck::pass() : RuleCheck::fail(__('registration.rules.section_open.failed'));
    }
}
