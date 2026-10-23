<?php

namespace App\Domain\Grading\Services;

use App\Domain\Curriculum\Enums\GradingBasis;
use App\Domain\Grading\Enums\GradeAppliesTo;
use App\Domain\Grading\Settings\GradingSettings;
use App\Models\Grading\GradeScale;
use App\Models\Grading\GradeScaleItem;
use App\Models\Registration\Enrollment;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * The grades an enrollment may receive: the default scale's grades for the course's grading
 * basis (letters, or P/NP), up to the retake cap for retakes. Turns a percentage into the
 * grade whose range holds it.
 */
class GradeOptions
{
    private ?GradeScale $scale = null;

    public function __construct(private readonly GradingSettings $settings) {}

    public function scale(): GradeScale
    {
        return $this->scale ??= GradeScale::query()->where('is_default', true)->with('items')->first()
            ?? throw ValidationException::withMessages(['grade_code' => __('grading.errors.no_default_scale')]);
    }

    /**
     * Requires the enrollment's version.
     *
     * @return Collection<int, GradeScaleItem>
     */
    public function for(Enrollment $enrollment): Collection
    {
        $basis = $enrollment->version->grading_basis === GradingBasis::PassFail ? GradeAppliesTo::PassFail : GradeAppliesTo::Graded;
        $items = $this->scale()->items->filter(fn (GradeScaleItem $item) => in_array($item->applies_to, [$basis, GradeAppliesTo::Both], true));
        $cap = $enrollment->is_retake ? $this->retakeCap() : null;

        if ($cap !== null) {
            $items = $items->reject(fn (GradeScaleItem $item) => $item->grade_points !== null && (float) $item->grade_points > (float) $cap->grade_points);
        }

        return $items->values();
    }

    /**
     * The grade for a code, or for a percentage when no code is given.
     */
    public function resolve(Enrollment $enrollment, ?string $code, ?string $score): ?GradeScaleItem
    {
        $options = $this->for($enrollment);

        if (filled($code)) {
            return $options->firstWhere('code', $code)
                ?? throw ValidationException::withMessages(['grade_code' => __('grading.errors.grade_not_allowed', ['grade' => $code])]);
        }

        if (blank($score)) {
            return null;
        }

        return $options->first(fn (GradeScaleItem $item) => $item->min_percentage !== null && $item->max_percentage !== null
            && (float) $score >= (float) $item->min_percentage && (float) $score <= (float) $item->max_percentage)
            ?? throw ValidationException::withMessages(['score' => __('grading.errors.score_without_grade', ['score' => $score])]);
    }

    private function retakeCap(): ?GradeScaleItem
    {
        return $this->settings->retake_max_grade === null ? null : $this->scale()->items->firstWhere('code', $this->settings->retake_max_grade);
    }
}
