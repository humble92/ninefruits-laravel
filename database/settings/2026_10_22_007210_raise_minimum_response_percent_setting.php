<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Default minimum response rate 25% (decided 2026-10-23); a value someone set is kept.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->update('evaluations.minimum_response_percent', fn (int $percent) => $percent === 0 ? 25 : $percent);
    }

    public function down(): void
    {
        $this->migrator->update('evaluations.minimum_response_percent', fn (int $percent) => $percent === 25 ? 0 : $percent);
    }
};
