<?php

namespace App\Support\Database;

use Illuminate\Support\Facades\DB;

/**
 * Constraints and generated columns Laravel's schema builder does not express.
 * Arguments are identifiers and SQL fragments written in migrations, never user input.
 */
final class Constraint
{
    /**
     * CHECK that a translatable jsonb column holds an object ({"en": "..."}).
     */
    public static function translatable(string $table, string $column, bool $nullable = false): void
    {
        $condition = $nullable
            ? "{$column} IS NULL OR jsonb_typeof({$column}) = 'object'"
            : "jsonb_typeof({$column}) = 'object'";

        // @phpstan-ignore ninefruits.dynamicRawSql (identifiers from migrations)
        DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$table}_{$column}_object_check CHECK ({$condition})");
    }

    /**
     * Generated daterange from a start and an optional end date (both inclusive), plus a
     * CHECK that the end is not before the start.
     */
    public static function datePeriod(string $table, string $column, string $startColumn, string $endColumn): void
    {
        // @phpstan-ignore ninefruits.dynamicRawSql (identifiers from migrations)
        DB::statement(<<<SQL
            ALTER TABLE {$table}
                ADD COLUMN {$column} daterange GENERATED ALWAYS AS (daterange({$startColumn}, {$endColumn}, '[]')) STORED,
                ADD CONSTRAINT {$table}_{$column}_order_check CHECK ({$endColumn} IS NULL OR {$endColumn} >= {$startColumn})
            SQL);
    }

    /**
     * EXCLUDE constraint over a period column, optionally per group (e.g. term type).
     *
     * @param  list<string>  $equalColumns
     */
    public static function noOverlap(string $table, string $periodColumn, array $equalColumns = []): void
    {
        $elements = implode(', ', [...array_map(fn (string $column) => "{$column} WITH =", $equalColumns), "{$periodColumn} WITH &&"]);

        // @phpstan-ignore ninefruits.dynamicRawSql (identifiers from migrations)
        DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$table}_no_overlap_excl EXCLUDE USING gist ({$elements})");
    }

    /**
     * Trigram index on all translations of a column, for language independent search.
     */
    public static function translationSearchIndex(string $table, string $column): void
    {
        // @phpstan-ignore ninefruits.dynamicRawSql (identifiers from migrations)
        DB::statement("CREATE INDEX {$table}_{$column}_search_index ON {$table} USING gin (util.i18n_search_text({$column}) gin_trgm_ops)");
    }

    /**
     * @param  list<string>  $tables
     */
    public static function audit(array $tables): void
    {
        foreach ($tables as $table) {
            DB::select('SELECT util.enable_audit(?::regclass)', [$table]);
        }
    }
}
