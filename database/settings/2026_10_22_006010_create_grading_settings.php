<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Grading policy (porting/decisions/0011-grades-and-term-records.md). Defaults suit a Korean
 * university; the legacy YUST profile sets its own values.
 */
return new class extends SettingsMigration
{
    private const array DEFAULTS = [
        'grading.gpa_decimals' => 2,
        'grading.gpa_rounding' => 'round',
        'grading.retake_policy' => 'latest',
        'grading.retake_max_grade' => null,
        'grading.standing_regular_terms_only' => true,
        'grading.warning_gpa_below' => 1.75,
        'grading.dismissal_warning_count' => 0,
        'grading.dismissal_consecutive_only' => false,
        'grading.honors_min_gpa' => 4.0,
        'grading.honors_min_credits' => 15,
        'grading.honors_exclude_failing' => true,
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
