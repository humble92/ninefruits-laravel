<?php

namespace App\Domain\Person\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Student record options (settings group `student_records`).
 */
class StudentRecordSettings extends Settings
{
    /**
     * Pattern for new student numbers. Tokens: {YYYY} {YY} admission year, {SEQ:n} sequence
     * of n digits per prefix (everything before {SEQ}). Business rules never read meaning
     * out of the number; it is only an identifier.
     */
    public string $student_number_pattern;

    /** Days an account invitation stays valid. */
    public int $invitation_valid_days;

    /** Second majors a student record may have at the same time. */
    public int $max_second_majors;

    /** Minors a student record may have at the same time. */
    public int $max_minors;

    public static function group(): string
    {
        return 'student_records';
    }
}
