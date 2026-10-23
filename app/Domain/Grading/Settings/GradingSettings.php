<?php

namespace App\Domain\Grading\Settings;

use App\Domain\Grading\Enums\GpaRounding;
use App\Domain\Grading\Enums\RetakePolicy;
use Spatie\LaravelSettings\Settings;

/**
 * Grading policy (settings group `grading`): GPA precision, retakes, academic standing and
 * honors.
 */
class GradingSettings extends Settings
{
    /** Decimals of term and cumulative GPA (legacy YUST: 3). */
    public int $gpa_decimals;

    public string $gpa_rounding;

    public string $retake_policy;

    /** Highest grade a retake may receive (e.g. A0), or null for no cap. */
    public ?string $retake_max_grade;

    /** Judge warnings and count them only in regular terms (not summer or winter). */
    public bool $standing_regular_terms_only;

    /** A term GPA below this is an academic warning (legacy YUST: 1.3). */
    public float $warning_gpa_below;

    /** Warnings that flag a student for dismissal review; 0 turns it off (legacy YUST: 4). */
    public int $dismissal_warning_count;

    /** Count only consecutive warnings (the legacy counted all). */
    public bool $dismissal_consecutive_only;

    public float $honors_min_gpa;

    /** GPA credits in the term a student needs for honors. */
    public float $honors_min_credits;

    /** No honors with a failed course in the term. */
    public bool $honors_exclude_failing;

    public function rounding(): GpaRounding
    {
        return GpaRounding::from($this->gpa_rounding);
    }

    public function retakePolicy(): RetakePolicy
    {
        return RetakePolicy::from($this->retake_policy);
    }

    public static function group(): string
    {
        return 'grading';
    }
}
