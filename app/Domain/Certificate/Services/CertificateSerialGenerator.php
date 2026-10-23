<?php

namespace App\Domain\Certificate\Services;

use App\Domain\Certificate\Settings\CertificateSettings;
use App\Models\Certificate\CertificateType;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Serial numbers from the configured pattern, e.g. {TYPE}-{YYYY}-{SEQ:6} → ENR-2026-000001.
 * The sequence is per prefix and advanced with one atomic upsert, so two issues never share
 * a number (the legacy had no ledger and no numbers).
 */
class CertificateSerialGenerator
{
    public function __construct(private readonly CertificateSettings $settings) {}

    public function next(CertificateType $type, ?CarbonImmutable $at = null): string
    {
        $pattern = $this->settings->serial_pattern;

        if (! preg_match('/^(.*)\{SEQ:(\d{1,2})\}(.*)$/', $pattern, $parts)) {
            throw new InvalidArgumentException("The certificate serial pattern [{$pattern}] needs exactly one {SEQ:n} token.");
        }

        [, $before, $digits, $after] = $parts;
        $year = ($at ?? CarbonImmutable::now())->year;
        $expand = fn (string $text) => strtr($text, ['{TYPE}' => $type->code, '{YYYY}' => (string) $year, '{YY}' => substr((string) $year, -2)]);
        $prefix = $expand($before);

        $value = (int) DB::scalar(<<<'SQL'
            INSERT INTO certificate_number_sequences (prefix, last_value, created_at, updated_at)
            VALUES (?, 1, now(), now())
            ON CONFLICT (prefix) DO UPDATE SET last_value = certificate_number_sequences.last_value + 1, updated_at = now()
            RETURNING last_value
            SQL, [$prefix]);

        return $prefix.str_pad((string) $value, (int) $digits, '0', STR_PAD_LEFT).$expand($after);
    }
}
