<?php

namespace App\Domain\Grading\Actions;

use App\Models\Grading\GradeScale;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Creates or updates a grade scale. The default scale is the institution's scale, fixed at
 * installation (decided 2026-10-23): it can be set only while there is none, and never
 * cleared or moved, because grades and GPAs already given are on it.
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

            $wantsDefault = ! empty($attributes['is_default']);
            $hasDefault = GradeScale::query()->where('is_default', true)
                ->when($scale->exists, fn ($query) => $query->whereKeyNot($scale->getKey()))->exists();

            if ($wantsDefault && $hasDefault) {
                throw ValidationException::withMessages(['is_default' => __('grading.errors.scale_fixed')]);
            }

            if ($scale->exists && $scale->is_default && array_key_exists('is_default', $attributes) && ! $wantsDefault) {
                throw ValidationException::withMessages(['is_default' => __('grading.errors.scale_fixed')]);
            }

            $scale->fill($attributes)->save();

            return $scale;
        });
    }
}
