<?php

namespace App\Domain\Certificate\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Certificate options (settings group `certificates`).
 */
class CertificateSettings extends Settings
{
    /** Serial number pattern: {TYPE} type code, {YYYY}/{YY} issue year, {SEQ:n} sequence per prefix. */
    public string $serial_pattern;

    /** Days the public verification page confirms an issue; 0 for no limit. */
    public int $verification_valid_days;

    /**
     * Default signer by locale, used when a template has none (e.g. the president's title).
     *
     * @var array<string, string>
     */
    public array $signer;

    public static function group(): string
    {
        return 'certificates';
    }
}
