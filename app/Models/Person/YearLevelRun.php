<?php

namespace App\Models\Person;

use App\Models\Organization\Program;
use App\Models\Term\Term;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $term_id
 * @property int|null $program_id
 * @property string $status
 * @property CarbonImmutable $cutoff_date
 * @property CarbonImmutable|null $scheduled_at
 * @property CarbonImmutable|null $applied_at
 * @property int|null $approved_by_id
 * @property-read Term $term
 * @property-read Program|null $program
 */
class YearLevelRun extends Model
{
    protected $fillable = ['term_id', 'program_id', 'cutoff_date', 'status', 'scheduled_at', 'applied_at', 'approved_by_id'];

    protected $attributes = ['program_id' => null, 'status' => 'draft', 'scheduled_at' => null, 'applied_at' => null, 'approved_by_id' => null];

    protected function casts(): array
    {
        return ['cutoff_date' => 'immutable_date', 'scheduled_at' => 'immutable_datetime', 'applied_at' => 'immutable_datetime'];
    }

    /** @return BelongsTo<Term, $this> */
    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    /** @return BelongsTo<Program, $this> */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /** @return HasMany<YearLevelChange, $this> */
    public function changes(): HasMany
    {
        return $this->hasMany(YearLevelChange::class);
    }
}
