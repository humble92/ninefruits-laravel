<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Graduation options (porting/decisions/0016-graduation-and-reporting.md).
 */
return new class extends SettingsMigration
{
    private const array DEFAULTS = [
        // Tokens: {YYYY}/{YY} graduation year, {PROGRAM} program code, {SEQ:n} sequence per prefix.
        'graduation.degree_number_pattern' => '{PROGRAM}-{YYYY}-{SEQ:5}',
        // Cumulative GPA required to graduate; null for none.
        'graduation.minimum_cumulative_gpa' => 2.0,
    ];

    public function up(): void
    {
        foreach (self::DEFAULTS as $name => $value) {
            $this->migrator->add($name, $value);
        }
    }

    public function down(): void
    {
        foreach (array_keys(self::DEFAULTS) as $name) {
            $this->migrator->delete($name);
        }
    }
};
