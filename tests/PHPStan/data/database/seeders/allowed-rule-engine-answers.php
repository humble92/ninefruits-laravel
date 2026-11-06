<?php

use App\Domain\Registration\Data\RegistrationContext;
use App\Domain\Registration\Data\RuleCheck;
use App\Domain\Registration\Services\RegistrationRuleEngine;
use Illuminate\Support\Collection;

function allowedRuleEngineAnswers(RegistrationRuleEngine $engine, RegistrationContext $context): void
{
    $engine->evaluateWith($context, new Collection, fn () => RuleCheck::pass());
}
