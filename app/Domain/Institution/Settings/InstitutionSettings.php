<?php

namespace App\Domain\Institution\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Per-installation settings (settings group `institution`).
 */
class InstitutionSettings extends Settings
{
    /** @var array<string, string> Institution name by locale. */
    public array $name;

    /** @var list<string> Locales offered to users. Each needs a lang/{locale} directory. */
    public array $supported_locales;

    public string $default_locale;

    /** IANA time zone used for academic calendar decisions. Stored times are UTC. */
    public string $timezone;

    public static function group(): string
    {
        return 'institution';
    }
}
