<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Lao (lo) ships with the application from 2026-10-23: it joins the default list of
 * supported locales. Installations that removed a locale on purpose keep their list.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->update('institution.supported_locales', function (array $locales): array {
            $default = ['en', 'ko', 'zh_CN'];

            return array_diff($locales, $default) === [] && array_diff($default, $locales) === []
                ? [...$locales, 'lo']
                : $locales;
        });
    }

    public function down(): void
    {
        $this->migrator->update('institution.supported_locales', fn (array $locales): array => array_values(array_diff($locales, ['lo'])));
    }
};
