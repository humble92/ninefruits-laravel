<?php

namespace App\Models\Registration;

use App\Models\Concerns\HasTranslatableAttributes;
use App\Models\Contracts\Translatable;
use App\Models\Term\TermType;
use App\Support\Targeting\TargetCriteria;
use App\Support\Targeting\TargetSubject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Credits a student may register for in a term (legacy rule by student number digits).
 * Policies match by term type and eligibility (programs, units, admission years ...); the
 * matching policy with the highest priority applies.
 *
 * @property int $id
 * @property string $name
 * @property int|null $term_type_id
 * @property TargetCriteria $eligibility
 * @property string|null $min_credits
 * @property string $max_credits
 * @property int $priority
 * @property bool $is_active
 * @property-read TermType|null $termType
 */
class CreditLimitPolicy extends Model implements Translatable
{
    use HasTranslatableAttributes;

    /** @var list<string> */
    public array $translatable = ['name'];

    protected $fillable = ['name', 'term_type_id', 'eligibility', 'min_credits', 'max_credits', 'priority', 'is_active'];

    protected $attributes = ['term_type_id' => null, 'eligibility' => null, 'min_credits' => null, 'priority' => 0, 'is_active' => true];

    protected function casts(): array
    {
        return [
            'eligibility' => TargetCriteria::class,
            'min_credits' => 'decimal:1',
            'max_credits' => 'decimal:1',
            'priority' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<TermType, $this>
     */
    public function termType(): BelongsTo
    {
        return $this->belongsTo(TermType::class);
    }

    /**
     * The policy for a student in a term type, or null when none matches (no limit).
     */
    public static function applicableTo(TargetSubject $student, int $termTypeId): ?self
    {
        return self::query()->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('term_type_id')->orWhere('term_type_id', $termTypeId))
            ->orderByDesc('priority')->orderBy('id')->get()
            ->first(fn (self $policy) => $policy->eligibility->matches($student));
    }
}
