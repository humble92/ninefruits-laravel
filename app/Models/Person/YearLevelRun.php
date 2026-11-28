<?php

namespace App\Models\Person;

use App\Models\Term\Term;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $term_id
 * @property string $status
 * @property CarbonImmutable $cutoff_date
 * @property CarbonImmutable|null $scheduled_at
 * @property CarbonImmutable|null $applied_at
 * @property int|null $approved_by_id
 * @property-read Term $term
 */
class YearLevelRun extends Model
{
    protected $fillable = ['term_id', 'cutoff_date', 'status', 'scheduled_at', 'applied_at', 'approved_by_id'];

    protected $attributes = ['status' => 'draft', 'scheduled_at' => null, 'applied_at' => null, 'approved_by_id' => null];

    protected function casts(): array
    {
        return ['cutoff_date' => 'immutable_date', 'scheduled_at' => 'immutable_datetime', 'applied_at' => 'immutable_datetime'];
    }

    /** @return BelongsTo<Term, $this> */
    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    /** @return HasMany<YearLevelChange, $this> */
    public function changes(): HasMany
    {
        return $this->hasMany(YearLevelChange::class);
    }
}
