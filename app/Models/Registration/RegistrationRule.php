<?php

namespace App\Models\Registration;

use App\Domain\Registration\Enums\RuleCode;
use App\Domain\Registration\Enums\RuleSeverity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Configuration of one rule in a term's rule set.
 *
 * @property int $id
 * @property int $registration_rule_set_id
 * @property RuleCode $rule_code
 * @property bool $is_enabled
 * @property RuleSeverity $severity
 * @property bool $allows_override
 * @property array<string, mixed>|null $parameters
 * @property-read RegistrationRuleSet $ruleSet
 */
class RegistrationRule extends Model
{
    protected $fillable = ['registration_rule_set_id', 'rule_code', 'is_enabled', 'severity', 'allows_override', 'parameters'];

    protected $attributes = ['is_enabled' => true, 'severity' => 'block', 'allows_override' => true, 'parameters' => null];

    protected function casts(): array
    {
        return [
            'rule_code' => RuleCode::class,
            'is_enabled' => 'boolean',
            'severity' => RuleSeverity::class,
            'allows_override' => 'boolean',
            'parameters' => 'array',
        ];
    }

    /**
     * The column holds a JSON object; no parameters are stored as null, not as [].
     *
     * @param  array<string, mixed>|null  $value
     */
    public function setParametersAttribute(?array $value): void
    {
        $this->attributes['parameters'] = $value === null || $value === [] ? null : json_encode($value, JSON_THROW_ON_ERROR);
    }

    /**
     * @return BelongsTo<RegistrationRuleSet, $this>
     */
    public function ruleSet(): BelongsTo
    {
        return $this->belongsTo(RegistrationRuleSet::class, 'registration_rule_set_id');
    }
}
