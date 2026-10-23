<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Grade caps for relative evaluation (decided 2026-10-23): A at most 30%, B at most 40%
 * of a section's graded students, 1 percent point of tolerance, sections under 10
 * students exempt.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('grading.relative_grade_caps', ['A' => 30, 'B' => 40]);
        $this->migrator->add('grading.relative_cap_tolerance_percent', 1);
        $this->migrator->add('grading.relative_minimum_students', 10);
    }

    public function down(): void
    {
        $this->migrator->delete('grading.relative_grade_caps');
        $this->migrator->delete('grading.relative_cap_tolerance_percent');
        $this->migrator->delete('grading.relative_minimum_students');
    }
};
