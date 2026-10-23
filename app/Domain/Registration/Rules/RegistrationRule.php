<?php

namespace App\Domain\Registration\Rules;

use App\Domain\Registration\Data\RegistrationContext;
use App\Domain\Registration\Data\RuleCheck;
use App\Domain\Registration\Enums\AttemptAction;
use App\Domain\Registration\Enums\RuleCode;
use App\Domain\Registration\Enums\RuleSeverity;

/**
 * A registration rule (porting/porting-plan.md section 10.6). Rules only answer; the engine
 * applies the term's configuration.
 */
interface RegistrationRule
{
    public function code(): RuleCode;

    /**
     * Configuration when the term has no rule set (and the starting point of a new one).
     *
     * @return array{severity: RuleSeverity, allows_override: bool, parameters: array<string, mixed>}
     */
    public function defaults(): array;

    public function appliesTo(AttemptAction $action): bool;

    /**
     * @param  array<string, mixed>  $parameters
     */
    public function check(RegistrationContext $context, array $parameters): RuleCheck;
}
