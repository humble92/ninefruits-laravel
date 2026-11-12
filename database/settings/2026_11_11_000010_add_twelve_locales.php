<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Twelve languages ship with the application from 2026-11-11 (German, Russian, Malay,
 * Filipino, French, Japanese, Mongolian, Nepali, Thai, Turkish, Uzbek, Vietnamese).
 * They join the default list of supported locales; installations that changed the list
 * on purpose keep it and enable the new languages in the institution settings.
 */
return new class extends SettingsMigration
{
    private const ADDED = ['de', 'ru', 'ms', 'fil', 'fr', 'ja', 'mn', 'ne', 'th', 'tr', 'uz', 'vi'];

    public function up(): void
    {
        $this->migrator->update('institution.supported_locales', function (array $locales): array {
            $default = ['en', 'ko', 'zh_CN', 'lo'];

            return array_diff($locales, $default) === [] && array_diff($default, $locales) === []
                ? [...$locales, ...self::ADDED]
                : $locales;
        });
    }

    public function down(): void
    {
        $this->migrator->update('institution.supported_locales', fn (array $locales): array => array_values(array_diff($locales, self::ADDED)));
    }
};
