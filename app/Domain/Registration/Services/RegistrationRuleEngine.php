<?php

namespace App\Domain\Registration\Services;

use App\Domain\Registration\Data\RegistrationContext;
use App\Domain\Registration\Data\RuleEvaluation;
use App\Domain\Registration\Data\RuleResult;
use App\Domain\Registration\Enums\RuleCode;
use App\Domain\Registration\Enums\RuleSeverity;
use App\Models\Registration\RegistrationOverride;
use App\Models\Registration\RegistrationRule as RuleConfiguration;
use App\Models\Registration\RegistrationRuleSet;
use Illuminate\Support\Collection;

/**
 * Runs every applicable rule and collects all results (docs/porting-plan.md section 10.6).
 * Per rule the term's rule set decides: enabled, block or warn, override allowed,
 * parameters. Without a rule set the catalog defaults apply. A failed rule is overridden
 * when overrides are allowed and staff waived it for this action or the student has an
 * override in force for the term (and section or course). Seats are not a rule: they are
 * taken atomically afterwards.
 */
class RegistrationRuleEngine
{
    /** @var array<int, Collection<string, RuleConfiguration>> Rule configuration per term */
    private array $configurations = [];

    public function __construct(private readonly RuleCatalog $catalog) {}

    public function evaluate(RegistrationContext $context): RuleEvaluation
    {
        // Callers pass sections loaded for their screens; the rules need these.
        $context->section->loadMissing(['course', 'version', 'term', 'session', 'meetings', 'seatGroups']);
        $configuration = $this->configuration($context->section->term_id);
        $overrides = RegistrationOverride::query()->where('student_record_id', $context->record->id)
            ->where('term_id', $context->section->term_id)->inForce($context->at)->get()
            ->filter(fn (RegistrationOverride $override) => $override->covers($context->section));
        $results = [];

        foreach ($this->catalog->all() as $rule) {
            $code = $rule->code();
            $defaults = $rule->defaults();
            $configured = $configuration->get($code->value);

            if (! $rule->appliesTo($context->action) || (! $code->isCore() && $configured !== null && ! $configured->is_enabled)) {
                continue;
            }

            $severity = $code->isCore() ? RuleSeverity::Block : ($configured->severity ?? $defaults['severity']);
            $allowsOverride = $code->isCore() ? $defaults['allows_override'] : ($configured->allows_override ?? $defaults['allows_override']);
            $parameters = [...$defaults['parameters'], ...($configured->parameters ?? [])];

            $check = $rule->check($context, $parameters);
            $overridden = ! $check->passed && $allowsOverride && (
                in_array($code, $context->waived, true)
                || ($code === RuleCode::Window && $context->byStaff)
                || $overrides->contains(fn (RegistrationOverride $override) => $override->rule_code === $code)
            );

            $results[] = new RuleResult($code, $check->passed, $severity, $overridden, $check->message, $check->details);
        }

        return new RuleEvaluation($results);
    }

    /**
     * @return Collection<string, RuleConfiguration>
     */
    private function configuration(int $termId): Collection
    {
        return $this->configurations[$termId] ??= RegistrationRuleSet::query()->where('term_id', $termId)->first()?->rules()->get()
            ->keyBy(fn (RuleConfiguration $rule) => $rule->rule_code->value) ?? new Collection;
    }
}
