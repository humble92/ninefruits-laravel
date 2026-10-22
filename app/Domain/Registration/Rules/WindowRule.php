<?php

namespace App\Domain\Registration\Rules;

use App\Domain\Registration\Data\RegistrationContext;
use App\Domain\Registration\Data\RuleCheck;
use App\Domain\Registration\Enums\AttemptAction;
use App\Domain\Registration\Enums\RuleCode;
use App\Domain\Registration\Enums\RuleSeverity;
use App\Domain\Term\Enums\TermWindowType;
use App\Domain\Term\Services\TermResolver;

/**
 * The action must fall in an open window for the student: registration (term) or add/drop
 * (the section's session) to enroll or drop, the cart window to plan, withdrawal (session)
 * to withdraw. Staff adjustments waive it.
 */
class WindowRule implements RegistrationRule
{
    public function __construct(private readonly TermResolver $terms) {}

    public function code(): RuleCode
    {
        return RuleCode::Window;
    }

    public function defaults(): array
    {
        return ['severity' => RuleSeverity::Block, 'allows_override' => true, 'parameters' => []];
    }

    public function appliesTo(AttemptAction $action): bool
    {
        return true;
    }

    public function check(RegistrationContext $context, array $parameters): RuleCheck
    {
        $section = $context->section;
        $termOpen = fn (TermWindowType $type) => $this->terms->termsOpenFor($type, $context->record, $context->at)->contains('id', $section->term_id);
        $sessionOpen = fn (TermWindowType $type) => $this->terms->isOpenInSession($type, $section->session, $context->record, $context->at);

        $open = match ($context->action) {
            AttemptAction::Cart => $termOpen(TermWindowType::RegistrationCart) || $termOpen(TermWindowType::Registration),
            AttemptAction::Withdraw => $sessionOpen(TermWindowType::Withdrawal),
            default => $termOpen(TermWindowType::Registration) || $sessionOpen(TermWindowType::AddDrop),
        };

        return $open ? RuleCheck::pass() : RuleCheck::fail(__('registration.rules.window.failed.'.$context->action->value));
    }
}
