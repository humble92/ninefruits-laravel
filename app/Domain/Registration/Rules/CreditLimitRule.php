<?php

namespace App\Domain\Registration\Rules;

use App\Domain\Registration\Data\RegistrationContext;
use App\Domain\Registration\Data\RuleCheck;
use App\Domain\Registration\Enums\AttemptAction;
use App\Domain\Registration\Enums\RuleCode;
use App\Domain\Registration\Enums\RuleSeverity;
use App\Models\Registration\CreditLimitPolicy;
use App\Models\Registration\Enrollment;

/**
 * Total credits in the term within the applicable credit limit policy (legacy: by
 * admission year and nursing, read from student number digits). Courses that do not count
 * toward the limit (legacy credit bank codes 009*, 900*) are left out. No matching policy:
 * no limit.
 */
class CreditLimitRule implements RegistrationRule
{
    public function code(): RuleCode
    {
        return RuleCode::CreditLimit;
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
        $version = $context->section->version;
        $policy = CreditLimitPolicy::applicableTo($context->record, $context->section->term->term_type_id);

        if ($policy === null || ! $version->counts_toward_credit_limit) {
            return RuleCheck::pass();
        }

        $current = $context->termEnrollments()->filter(fn (Enrollment $enrollment) => $enrollment->counts_toward_credit_limit)->sum(fn (Enrollment $enrollment) => (float) $enrollment->credits);
        $total = $current + (float) $version->credits;
        $limit = (float) $policy->max_credits;
        $details = ['credits' => $total, 'max_credits' => $limit, 'policy_id' => $policy->id];

        return $total <= $limit
            ? RuleCheck::pass(null, $details)
            : RuleCheck::fail(__('registration.rules.credit_limit.failed', ['total' => $total, 'limit' => $limit]), $details);
    }
}
