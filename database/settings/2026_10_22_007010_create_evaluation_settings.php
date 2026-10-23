<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Course evaluation options (docs/decisions/0012-course-evaluation.md).
 */
return new class extends SettingsMigration
{
    private const array DEFAULTS = [
        'evaluations.grades_require_evaluation' => true,
        'evaluations.reject_uniform_answers' => false,
        'evaluations.minimum_responses' => 5,
        'evaluations.minimum_response_percent' => 0,
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
