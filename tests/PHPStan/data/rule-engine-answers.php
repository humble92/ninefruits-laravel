<?php

use App\Domain\Registration\Data\RegistrationContext;
use App\Domain\Registration\Data\RuleCheck;
use App\Domain\Registration\Services\RegistrationRuleEngine;
use Illuminate\Support\Collection;

function ruleEngineAnswers(RegistrationRuleEngine $engine, ?RegistrationRuleEngine $maybe, RegistrationContext $context): void
{
    $engine->evaluate($context);
    $engine->evaluateWith($context, new Collection, fn () => RuleCheck::pass());
    $maybe?->evaluateWith($context, new Collection, fn () => RuleCheck::pass());
    $answers = $engine->evaluateWith(...);
}
