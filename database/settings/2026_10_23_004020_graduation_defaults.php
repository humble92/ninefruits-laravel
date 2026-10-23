<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Decided 2026-10-23: a 2.0 cumulative GPA to graduate, degree numbers per program and year.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->update('graduation.minimum_cumulative_gpa', fn () => 2.0);
        $this->migrator->update('graduation.degree_number_pattern', fn () => '{PROGRAM}-{YYYY}-{SEQ:5}');
    }

    public function down(): void
    {
        $this->migrator->update('graduation.minimum_cumulative_gpa', fn () => null);
        $this->migrator->update('graduation.degree_number_pattern', fn () => '{YYYY}-{SEQ:5}');
    }
};
