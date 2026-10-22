<?php

namespace App\Models\Term;

use App\Domain\Term\Enums\TermWindowType;
use App\Models\Concerns\HasTranslatableAttributes;
use App\Models\Contracts\Translatable;
use App\Support\Targeting\TargetCriteria;
use Carbon\CarbonImmutable;
use Database\Factories\Term\TermWindowFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A business period of a term, e.g. registration for fourth-year students. Several windows
 * of the same type with different eligibility give staggered openings.
 *
 * @property int $id
 * @property int $term_id
 * @property TermWindowType $window_type
 * @property string|null $name
 * @property CarbonImmutable $opens_at
 * @property CarbonImmutable $closes_at
 * @property TargetCriteria $eligibility
 * @property-read Term $term
 */
class TermWindow extends Model implements Translatable
{
    /** @use HasFactory<TermWindowFactory> */
    use HasFactory, HasTranslatableAttributes;

    /** @var list<string> */
    public array $translatable = ['name'];

    protected $fillable = ['term_id', 'window_type', 'name', 'opens_at', 'closes_at', 'eligibility'];

    protected $attributes = ['name' => null, 'eligibility' => null];

    protected function casts(): array
    {
        return [
            'window_type' => TermWindowType::class,
            'opens_at' => 'immutable_datetime',
            'closes_at' => 'immutable_datetime',
            'eligibility' => TargetCriteria::class,
        ];
    }

    /**
     * @return BelongsTo<Term, $this>
     */
    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function isOpenAt(CarbonImmutable $at): bool
    {
        return $this->opens_at->lessThanOrEqualTo($at) && $this->closes_at->greaterThan($at);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeOpenAt(Builder $query, CarbonImmutable $at): void
    {
        $query->where('opens_at', '<=', $at)->where('closes_at', '>', $at);
    }
}
