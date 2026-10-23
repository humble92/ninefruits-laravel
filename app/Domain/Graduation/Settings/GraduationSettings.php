<?php

namespace App\Domain\Graduation\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Graduation options (settings group `graduation`).
 */
class GraduationSettings extends Settings
{
    /** Degree number pattern: {YYYY}/{YY} graduation year, {PROGRAM} program code, {SEQ:n} sequence per prefix. */
    public string $degree_number_pattern;

    /** Cumulative GPA required to graduate; null for none. */
    public ?float $minimum_cumulative_gpa;

    public static function group(): string
    {
        return 'graduation';
    }
}
