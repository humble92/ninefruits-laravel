<?php

namespace App\Domain\Grading\Services;

use App\Domain\Curriculum\Enums\GradingBasis;
use App\Domain\Grading\Data\GradeChoice;
use App\Domain\Grading\Enums\EvaluationMethod;
use App\Domain\Grading\Enums\GradeAppliesTo;
use App\Domain\Grading\Settings\GradingSettings;
use App\Models\Grading\GradeScale;
use App\Models\Grading\GradeScaleItem;
use App\Models\Registration\Enrollment;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * The grades an enrollment may receive and how an entry becomes a grade (decided
 * 2026-10-22):
 *
 * - Absolute evaluation: the score is the source. The grade follows from the score's range
 *   on the institution's scale (the absolute evaluation base, 4.3 or 4.5), and the grade on
 *   the comparison scale (the other one) is kept beside it. Only grades without a GPA value
 *   that apply to every course (e.g. I) are chosen directly.
 * - Relative evaluation: grades only, no score.
 * - Pass/fail courses: P or NP (or I), no score.
 *
 * A retake gets at most the configured cap grade: an absolute grade above it is lowered,
 * a relative one is refused.
 */
class GradeOptions
{
    /** @var array<string, GradeScale|null> */
    private array $scales = [];

    public function __construct(private readonly GradingSettings $settings) {}

    /**
     * The institution's scale: the default scale, which is also the base of absolute
     * evaluation (chosen in the grading policy).
     */
    public function scale(): GradeScale
    {
        return $this->scales['base'] ??= GradeScale::query()->where('is_default', true)->with('items')->first()
            ?? throw ValidationException::withMessages(['grade_code' => __('grading.errors.no_default_scale')]);
    }

    /**
     * The other scale with score ranges (e.g. 4.5 beside 4.3), or null.
     */
    public function comparisonScale(): ?GradeScale
    {
        if (! array_key_exists('comparison', $this->scales)) {
            $this->scales['comparison'] = GradeScale::query()->whereKeyNot($this->scale()->id)
                ->whereHas('items', fn ($item) => $item->whereNotNull('min_percentage'))
                ->with('items')->orderByDesc('max_grade_points')->orderBy('code')->first();
        }

        return $this->scales['comparison'];
    }

    /**
     * Grades to choose from directly for the enrollment (requires its version and section).
     *
     * @return Collection<int, GradeScaleItem>
     */
    public function for(Enrollment $enrollment): Collection
    {
        $items = $this->applicable($this->scale(), $enrollment);

        if ($this->takesScore($enrollment)) {
            $items = $items->filter(fn (GradeScaleItem $item) => $item->applies_to === GradeAppliesTo::Both && ! $item->counts_in_gpa);
        }

        return $items->values();
    }

    /**
     * Whether the grade comes from a score: absolute evaluation of a graded course.
     */
    public function takesScore(Enrollment $enrollment): bool
    {
        return $enrollment->section->evaluation_method === EvaluationMethod::Absolute
            && $enrollment->version->grading_basis === GradingBasis::Graded;
    }

    public function resolve(Enrollment $enrollment, ?string $code, ?string $score): GradeChoice
    {
        $score = blank($score) ? null : number_format((float) $score, 2, '.', '');
        $code = blank($code) ? null : $code;

        if (! $this->takesScore($enrollment)) {
            if ($score !== null) {
                throw ValidationException::withMessages(['score' => __('grading.errors.no_score_here')]);
            }

            return new GradeChoice($code === null ? null : $this->choose($enrollment, $code), null);
        }

        // Absolute: a directly chosen grade (e.g. I) without a score, or the score's grade.
        if ($code !== null && $score === null) {
            return new GradeChoice($this->choose($enrollment, $code), null);
        }

        if ($score === null) {
            return new GradeChoice(null, null);
        }

        $item = $this->byScore($this->scale(), $enrollment, $score)
            ?? throw ValidationException::withMessages(['score' => __('grading.errors.score_without_grade', ['score' => $score])]);
        $comparisonScale = $this->comparisonScale();
        $comparison = $comparisonScale === null ? null : $this->byScore($comparisonScale, $enrollment, $score);

        // A retake above the cap gets the cap grade (on the comparison scale too, where it has
        // the same grade); the score stays as entered.
        $cap = $this->cap($enrollment, $this->scale());

        if ($cap !== null && (float) $item->grade_points > (float) $cap->grade_points) {
            $item = $cap;
            $comparisonCap = $comparisonScale === null ? null : $this->cap($enrollment, $comparisonScale);
            $comparison = $comparisonCap !== null && $comparison !== null && (float) $comparison->grade_points > (float) $comparisonCap->grade_points ? $comparisonCap : $comparison;
        }

        return new GradeChoice($item, $score, $comparison);
    }

    private function choose(Enrollment $enrollment, string $code): GradeScaleItem
    {
        $item = $this->for($enrollment)->firstWhere('code', $code)
            ?? throw ValidationException::withMessages(['grade_code' => __('grading.errors.grade_not_allowed', ['grade' => $code])]);
        $cap = $this->cap($enrollment, $this->scale());

        if ($cap !== null && $item->grade_points !== null && (float) $item->grade_points > (float) $cap->grade_points) {
            throw ValidationException::withMessages(['grade_code' => __('grading.errors.above_retake_cap', ['grade' => $cap->code])]);
        }

        return $item;
    }

    private function byScore(GradeScale $scale, Enrollment $enrollment, string $score): ?GradeScaleItem
    {
        return $this->applicable($scale, $enrollment)->first(fn (GradeScaleItem $item) => $item->min_percentage !== null && $item->max_percentage !== null
            && (float) $score >= (float) $item->min_percentage && (float) $score <= (float) $item->max_percentage);
    }

    /**
     * @return Collection<int, GradeScaleItem>
     */
    private function applicable(GradeScale $scale, Enrollment $enrollment): Collection
    {
        $basis = $enrollment->version->grading_basis === GradingBasis::PassFail ? GradeAppliesTo::PassFail : GradeAppliesTo::Graded;

        return $scale->items->filter(fn (GradeScaleItem $item) => in_array($item->applies_to, [$basis, GradeAppliesTo::Both], true))->values();
    }

    private function cap(Enrollment $enrollment, GradeScale $scale): ?GradeScaleItem
    {
        return $enrollment->is_retake && $this->settings->retake_max_grade !== null
            ? $scale->items->firstWhere('code', $this->settings->retake_max_grade)
            : null;
    }
}
