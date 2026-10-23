<?php

namespace App\Console\Commands\Naming;

use App\Console\Commands\AuditedCommand;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Checks table and column names in migrations against docs/development/naming.md.
 */
#[Signature('naming:check {--path=* : Migration directories (default: database/migrations)}')]
#[Description('Check migration table and column names against the naming conventions')]
class CheckNaming extends Command
{
    /**
     * Framework and package tables and columns that keep their upstream names.
     *
     * @var list<string>
     */
    private const array UPSTREAM_MIGRATIONS = [
        '0001_01_01_000001_create_cache_table.php',
        '0001_01_01_000002_create_jobs_table.php',
        '2026_10_22_000100_create_settings_table.php',
        '2026_10_22_000400_create_features_table.php',
        '2026_10_22_000500_create_media_table.php',
    ];

    /**
     * Column types that store a point in time; they must be the time zone aware variants.
     *
     * @var list<string>
     */
    private const array TIMESTAMP_WITHOUT_TIME_ZONE = ['timestamp', 'timestamps', 'dateTime', 'nullableTimestamps', 'softDeletes'];

    private const string LANGUAGE_CODE = '/(^|_)(ko|kr|kor|en|eng|cn|zh|chn|ja|jp)(_|$)/';

    private const string FORBIDDEN_SUFFIX = '/(_old|_new|_backup|_bak|_tmp|_temp|_copy|_\d{6,8}|\d+)$/';

    public function handle(): int
    {
        $paths = $this->option('path') ?: [database_path('migrations')];
        $problems = [];

        foreach ($paths as $path) {
            foreach (File::glob(rtrim((string) $path, '/').'/*.php') as $file) {
                if (in_array(basename($file), self::UPSTREAM_MIGRATIONS, true)) {
                    continue;
                }

                foreach ($this->check((string) File::get($file)) as $problem) {
                    $problems[] = basename($file).': '.$problem;
                }
            }
        }

        foreach ($problems as $problem) {
            $this->line("<fg=red>✗</> {$problem}");
        }

        if ($problems === []) {
            $this->components->info('Migration names follow the conventions.');

            return self::SUCCESS;
        }

        $this->components->error(count($problems).' naming problem(s). See docs/development/naming.md.');

        return AuditedCommand::PROBLEMS_FOUND;
    }

    /**
     * @return list<string>
     */
    public function check(string $source): array
    {
        $problems = [];

        preg_match_all("/Schema::create\\(\\s*'(?:[a-z_]+\\.)?([^']+)'/", $source, $tables);

        foreach ($tables[1] as $table) {
            $problems = [...$problems, ...$this->checkName("table [{$table}]", $table)];
        }

        preg_match_all("/\\\$table->(\\w+)\\(\\s*'([^']+)'/", $source, $columns, PREG_SET_ORDER);

        foreach ($columns as [, $type, $column]) {
            if (in_array($type, ['index', 'unique', 'primary', 'foreign', 'dropColumn', 'dropIndex', 'renameColumn', 'comment'], true)) {
                continue;
            }

            $problems = [...$problems, ...$this->checkName("column [{$column}]", $column)];

            if (in_array($type, self::TIMESTAMP_WITHOUT_TIME_ZONE, true)) {
                $problems[] = "column [{$column}] uses {$type}(); use the Tz variant (timestamptz)";
            }

            if ($type === 'boolean' && ! preg_match('/^(is|has|can|should|counts|allows|requires|blocks)_/', $column)) {
                $problems[] = "boolean column [{$column}] should start with is_, has_, can_ or a verb";
            }

            if (in_array($type, ['date'], true) && ! str_ends_with($column, '_date')) {
                $problems[] = "date column [{$column}] should end with _date";
            }
        }

        foreach (self::TIMESTAMP_WITHOUT_TIME_ZONE as $type) {
            if (preg_match("/\\\$table->{$type}\\(\\s*\\)/", $source)) {
                $problems[] = "{$type}() without time zone; use {$type}Tz()";
            }
        }

        return array_values(array_unique($problems));
    }

    /**
     * @return list<string>
     */
    private function checkName(string $label, string $name): array
    {
        $problems = [];

        if (! preg_match('/^[a-z][a-z0-9_]*$/', $name)) {
            $problems[] = "{$label} is not snake_case";
        }

        if (preg_match(self::FORBIDDEN_SUFFIX, $name)) {
            $problems[] = "{$label} has a forbidden suffix (_old, _new, _backup, _tmp, dates or numbers)";
        }

        if (preg_match(self::LANGUAGE_CODE, $name)) {
            $problems[] = "{$label} contains a language code; store translations in one jsonb column";
        }

        return $problems;
    }
}
