<?php

namespace App\Domain\Registration\Services;

use App\Domain\Registration\Data\RegistrationContext;
use App\Domain\Registration\Data\RuleCheck;
use App\Domain\Registration\Data\RuleEvaluation;
use App\Domain\Registration\Data\RuleResult;
use App\Domain\Registration\Enums\RuleCode;
use App\Domain\Registration\Enums\RuleSeverity;
use App\Domain\Registration\Rules\RegistrationRule;
use App\Models\Registration\RegistrationOverride;
use App\Models\Registration\RegistrationRule as RuleConfiguration;
use App\Models\Registration\RegistrationRuleSet;
use Closure;
use Illuminate\Support\Collection;

/**
 * Runs every applicable rule and collects all results (porting/porting-plan.md section 10.6).
 * Per rule the term's rule set decides: enabled, block or warn, override allowed,
 * parameters. Without a rule set the catalog defaults apply. A failed rule is overridden
 * when overrides are allowed and staff waived it for this action or the student has an
 * override in force for the term (and section or course). Seats are not a rule: they are
 * taken atomically afterwards.
 */
class RegistrationRuleEngine
{
    /** @var array<int, list<array{0: RegistrationRule, 1: RuleSeverity, 2: bool, 3: array<string, mixed>}>> Configured rules per term */
    private array $configured = [];

    public function __construct(private readonly RuleCatalog $catalog) {}

    public function evaluate(RegistrationContext $context): RuleEvaluation
    {
        // Callers pass sections loaded for their screens; the rules need these.
        $context->section->loadMissing(['course', 'version', 'term', 'session', 'meetings', 'seatGroups']);
        $overrides = RegistrationOverride::query()->where('student_record_id', $context->record->id)
            ->where('term_id', $context->section->term_id)->inForce($context->at)->get()
            ->filter(fn (RegistrationOverride $override) => $override->covers($context->section));

        return $this->evaluateWith($context, $overrides, fn (RegistrationRule $rule, array $parameters) => $rule->check($context, $parameters));
    }

    /**
     * The term's configuration applied to checks the caller makes: evaluate() asks each rule,
     * bulk demo registrations answer from data loaded in memory.
     *
     * Reserved for demo seeders: a caller could answer "passed" for a rule that fails. The
     * PHPStan rule ninefruits.ruleEngineAnswers rejects calls outside database/seeders.
     *
     * @internal
     *
     * @param  Collection<int, RegistrationOverride>  $overrides  The student's overrides in force that cover the section
     * @param  Closure(RegistrationRule, array<string, mixed>): RuleCheck  $check
     */
    public function evaluateWith(RegistrationContext $context, Collection $overrides, Closure $check): RuleEvaluation
    {
        $results = [];

        foreach ($this->configuredRules($context->section->term_id) as [$rule, $severity, $allowsOverride, $parameters]) {
            if (! $rule->appliesTo($context->action)) {
                continue;
            }

            $code = $rule->code();
            $result = $check($rule, $parameters);
            $overridden = ! $result->passed && $allowsOverride && (
                in_array($code, $context->waived, true)
                || ($code === RuleCode::Window && $context->byStaff)
                || $overrides->contains(fn (RegistrationOverride $override) => $override->rule_code === $code)
            );

            $results[] = new RuleResult($code, $result->passed, $severity, $overridden, $result->message, $result->details);
        }

        return new RuleEvaluation($results);
    }

    /**
     * The term's enabled rules in catalog order with severity, whether overrides are allowed,
     * and parameters.
     *
     * @return list<array{0: RegistrationRule, 1: RuleSeverity, 2: bool, 3: array<string, mixed>}>
     */
    private function configuredRules(int $termId): array
    {
        if (isset($this->configured[$termId])) {
            return $this->configured[$termId];
        }

        $configuration = RegistrationRuleSet::query()->where('term_id', $termId)->first()?->rules()->get()
            ->keyBy(fn (RuleConfiguration $rule) => $rule->rule_code->value) ?? new Collection;
        $rules = [];

        foreach ($this->catalog->all() as $rule) {
            $code = $rule->code();
            $defaults = $rule->defaults();
            $configured = $configuration->get($code->value);

            if (! $code->isCore() && $configured !== null && ! $configured->is_enabled) {
                continue;
            }

            $rules[] = [
                $rule,
                $code->isCore() ? RuleSeverity::Block : ($configured->severity ?? $defaults['severity']),
                $code->isCore() ? $defaults['allows_override'] : ($configured->allows_override ?? $defaults['allows_override']),
                [...$defaults['parameters'], ...($configured->parameters ?? [])],
            ];
        }

        return $this->configured[$termId] = $rules;
    }
}
