<?php

namespace App\Domain\Registration\Actions;

use App\Domain\Registration\Services\RuleCatalog;
use App\Domain\Security\Services\AuditLogger;
use App\Models\Registration\RegistrationRule;
use App\Models\Registration\RegistrationRuleSet;
use App\Models\Term\Term;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Starts a term's rule set: a copy of another term's (usually the previous one of the same
 * type) or the catalog defaults. Rules added to the catalog later appear with defaults.
 */
class PrepareRuleSet
{
    public function __construct(
        private readonly RuleCatalog $catalog,
        private readonly AuditLogger $audit,
    ) {}

    public function handle(Term $term, ?RegistrationRuleSet $copyFrom, ?User $preparedBy): RegistrationRuleSet
    {
        if (RegistrationRuleSet::query()->where('term_id', $term->id)->exists()) {
            throw ValidationException::withMessages(['term_id' => __('registration.errors.rule_set_exists')]);
        }

        return DB::transaction(function () use ($term, $copyFrom, $preparedBy) {
            $set = RegistrationRuleSet::query()->create(['term_id' => $term->id, 'copied_from_rule_set_id' => $copyFrom?->id]);
            $copied = $copyFrom?->rules()->get()->keyBy(fn (RegistrationRule $rule) => $rule->rule_code->value);

            foreach ($this->catalog->all() as $rule) {
                if ($rule->code()->isCore()) {
                    continue;
                }

                $source = $copied?->get($rule->code()->value);
                $defaults = $rule->defaults();

                RegistrationRule::query()->create([
                    'registration_rule_set_id' => $set->id,
                    'rule_code' => $rule->code(),
                    'is_enabled' => $source->is_enabled ?? true,
                    'severity' => $source->severity ?? $defaults['severity'],
                    'allows_override' => $source->allows_override ?? $defaults['allows_override'],
                    'parameters' => $source->parameters ?? $defaults['parameters'],
                ]);
            }

            $this->audit->record('registration_rule_set.prepared', $set, null, ['term' => $term->code, 'copied_from' => $copyFrom?->term->code], $preparedBy);

            return $set;
        });
    }
}
