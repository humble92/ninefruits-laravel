<?php

namespace App\Domain\Graduation\Services;

use App\Domain\Graduation\Settings\GraduationSettings;
use App\Models\Organization\Program;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Degree numbers from the configured pattern, e.g. {YYYY}-{SEQ:5} → 2026-00001, one
 * atomic sequence per prefix (same mechanism as certificate serials and student numbers).
 */
class DegreeNumberGenerator
{
    private const string NEXT_VALUE_SQL = <<<'SQL'
        INSERT INTO degree_number_sequences (prefix, last_value, created_at, updated_at)
        VALUES (?, 1, now(), now())
        ON CONFLICT (prefix) DO UPDATE SET last_value = degree_number_sequences.last_value + 1, updated_at = now()
        RETURNING last_value
        SQL;

    public function __construct(private readonly GraduationSettings $settings) {}

    public function next(Program $program, CarbonImmutable $graduatedOn): string
    {
        $pattern = $this->settings->degree_number_pattern;

        if (! preg_match('/^(.*)\{SEQ:(\d{1,2})\}(.*)$/', $pattern, $parts)) {
            throw new InvalidArgumentException("The degree number pattern [{$pattern}] needs exactly one {SEQ:n} token.");
        }

        $tokens = ['{YYYY}' => $graduatedOn->format('Y'), '{YY}' => $graduatedOn->format('y'), '{PROGRAM}' => $program->code];
        $prefix = strtr($parts[1], $tokens);
        $suffix = strtr($parts[3], $tokens);
        $value = DB::selectOne(self::NEXT_VALUE_SQL, [$prefix])->last_value;

        return $prefix.str_pad((string) $value, (int) $parts[2], '0', STR_PAD_LEFT).$suffix;
    }
}
