<?php

namespace App\Models\Registration;

use App\Models\Term\Term;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The registration rules of a term, usually copied from the previous term and adjusted.
 *
 * @property int $id
 * @property int $term_id
 * @property int|null $copied_from_rule_set_id
 * @property string|null $note
 * @property-read Term $term
 * @property-read RegistrationRuleSet|null $copiedFrom
 * @property-read Collection<int, RegistrationRule> $rules
 */
class RegistrationRuleSet extends Model
{
    protected $fillable = ['term_id', 'copied_from_rule_set_id', 'note'];

    protected $attributes = ['copied_from_rule_set_id' => null, 'note' => null];

    /**
     * @return BelongsTo<Term, $this>
     */
    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    /**
     * @return BelongsTo<self, $this>
     */
    public function copiedFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'copied_from_rule_set_id');
    }

    /**
     * @return HasMany<RegistrationRule, $this>
     */
    public function rules(): HasMany
    {
        return $this->hasMany(RegistrationRule::class);
    }
}
