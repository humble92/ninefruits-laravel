<?php

namespace App\Models\Assessment;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One sitting of a kind (legacy CET_HISTORY): the date, the registration window, the
 * capacity and when its results were published to the students.
 *
 * @property int $id
 * @property int $assessment_type_id
 * @property CarbonImmutable $held_date
 * @property string|null $venue
 * @property CarbonImmutable|null $registration_opens_at
 * @property CarbonImmutable|null $registration_closes_at
 * @property int|null $capacity
 * @property string|null $pass_score
 * @property CarbonImmutable|null $results_published_at
 * @property-read AssessmentType $type
 * @property-read Collection<int, AssessmentRegistration> $registrations
 * @property-read Collection<int, AssessmentResult> $results
 */
class AssessmentSession extends Model
{
    protected $fillable = ['assessment_type_id', 'held_date', 'venue', 'registration_opens_at', 'registration_closes_at', 'capacity', 'pass_score', 'results_published_at'];

    protected $attributes = ['venue' => null, 'registration_opens_at' => null, 'registration_closes_at' => null, 'capacity' => null, 'pass_score' => null, 'results_published_at' => null];

    protected function casts(): array
    {
        return [
            'held_date' => 'immutable_date', 'registration_opens_at' => 'immutable_datetime', 'registration_closes_at' => 'immutable_datetime',
            'capacity' => 'integer', 'pass_score' => 'decimal:1', 'results_published_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<AssessmentType, $this>
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(AssessmentType::class, 'assessment_type_id');
    }

    /**
     * @return HasMany<AssessmentRegistration, $this>
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(AssessmentRegistration::class);
    }

    /**
     * @return HasMany<AssessmentResult, $this>
     */
    public function results(): HasMany
    {
        return $this->hasMany(AssessmentResult::class);
    }

    /**
     * Students may register now: both window ends set and around now.
     */
    public function isOpenForRegistration(?CarbonImmutable $at = null): bool
    {
        $at ??= CarbonImmutable::now();

        return $this->registration_opens_at !== null && $this->registration_closes_at !== null
            && $this->registration_opens_at <= $at && $at < $this->registration_closes_at;
    }

    /**
     * @param  Builder<AssessmentSession>  $query
     */
    public function scopeOpenForRegistration(Builder $query, ?CarbonImmutable $at = null): void
    {
        $at ??= CarbonImmutable::now();
        $query->where('registration_opens_at', '<=', $at)->where('registration_closes_at', '>', $at);
    }

    public function label(?string $locale = null): string
    {
        return $this->type->getTranslation('name', $locale ?? app()->getLocale()).' '.$this->held_date->toDateString();
    }

    /**
     * The pass score in force: the session's override, else the kind's.
     */
    public function passScore(): ?string
    {
        return $this->pass_score ?? $this->type->pass_score;
    }
}
