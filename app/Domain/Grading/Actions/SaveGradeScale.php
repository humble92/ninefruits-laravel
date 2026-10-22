<?php

namespace App\Domain\Grading\Actions;

use App\Models\Grading\GradeScale;
use Illuminate\Support\Facades\DB;

/**
 * Creates or updates a grade scale. Marking a scale as default clears the flag on the
 * others in the same transaction (one default, enforced by a partial unique index).
 */
class SaveGradeScale
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(?GradeScale $scale, array $attributes): GradeScale
    {
        return DB::transaction(function () use ($scale, $attributes) {
            $scale ??= new GradeScale;

            if (! empty($attributes['is_default'])) {
                GradeScale::query()->where('is_default', true)
                    ->when($scale->exists, fn ($query) => $query->whereKeyNot($scale->getKey()))
                    ->update(['is_default' => false]);
            }

            $scale->fill($attributes)->save();

            return $scale;
        });
    }
}
