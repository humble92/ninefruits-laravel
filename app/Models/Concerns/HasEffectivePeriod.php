<?php

namespace App\Models\Concerns;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Rows valid from effective_start_date to effective_end_date (inclusive, open ended when
 * null). The table also has a generated effective_period daterange used in queries.
 */
trait HasEffectivePeriod
{
    /**
     * @param  Builder<static>  $query
     */
    public function scopeEffectiveOn(Builder $query, DateTimeInterface|string|null $date = null): void
    {
        static::constrainToEffectiveOn($query, $date);
    }

    /**
     * Same as the effectiveOn scope, for queries not typed as this model's builder.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     */
    public static function constrainToEffectiveOn(Builder $query, DateTimeInterface|string|null $date = null): void
    {
        $date = $date === null ? CarbonImmutable::today()->toDateString() : CarbonImmutable::parse($date)->toDateString();

        $query->whereRaw('effective_period @> ?::date', [$date]);
    }

    public function isEffectiveOn(DateTimeInterface|string|null $date = null): bool
    {
        $date = $date === null ? CarbonImmutable::today() : CarbonImmutable::parse($date);

        return $this->effective_start_date->lessThanOrEqualTo($date)
            && ($this->effective_end_date === null || $this->effective_end_date->greaterThanOrEqualTo($date->startOfDay()));
    }
}
