<?php

namespace App\Domain\Person\Services;

use App\Domain\Person\Settings\StudentRecordSettings;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Issues student numbers from the configured pattern, e.g. {YYYY}{SEQ:5} → 202600001.
 *
 * The sequence is per prefix (the text before {SEQ}) and advanced with one atomic upsert,
 * so concurrent registrations never get the same number. The number is an identifier only:
 * no rule reads meaning from its digits (porting/porting-plan.md section 3.4).
 */
class StudentNumberGenerator
{
    public function __construct(private readonly StudentRecordSettings $settings) {}

    public function next(int $admissionYear): string
    {
        $pattern = $this->settings->student_number_pattern;

        if (! preg_match('/^(.*)\{SEQ:(\d{1,2})\}(.*)$/', $pattern, $parts)) {
            throw new InvalidArgumentException("The student number pattern [{$pattern}] needs exactly one {SEQ:n} token.");
        }

        [, $before, $digits, $after] = $parts;
        $prefix = $this->expand($before, $admissionYear);
        $suffix = $this->expand($after, $admissionYear);

        do {
            $value = (int) DB::scalar(<<<'SQL'
                INSERT INTO student_number_sequences (prefix, last_value, created_at, updated_at)
                VALUES (?, 1, now(), now())
                ON CONFLICT (prefix) DO UPDATE SET last_value = student_number_sequences.last_value + 1, updated_at = now()
                RETURNING last_value
                SQL, [$prefix]);

            $number = $prefix.str_pad((string) $value, (int) $digits, '0', STR_PAD_LEFT).$suffix;
            // Numbers entered by hand or imported may already use a value; skip those.
        } while ((bool) DB::scalar('SELECT util.student_number_exists(?)', [$number]));

        return $number;
    }

    private function expand(string $text, int $year): string
    {
        return strtr($text, [
            '{YYYY}' => (string) $year,
            '{YY}' => substr((string) $year, -2),
        ]);
    }
}
