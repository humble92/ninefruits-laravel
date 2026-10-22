<?php

namespace App\Support\Database;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Applies version-controlled SQL files from database/sql (functions, triggers, policies, views).
 */
final class SqlFile
{
    public static function path(string $relativePath): string
    {
        return database_path('sql/'.ltrim($relativePath, '/'));
    }

    public static function apply(string $relativePath, ?ConnectionInterface $connection = null): void
    {
        $path = self::path($relativePath);

        if (! is_file($path)) {
            throw new InvalidArgumentException("SQL file [{$relativePath}] does not exist.");
        }

        // @phpstan-ignore ninefruits.dynamicRawSql (version-controlled file from database/sql, never user input)
        ($connection ?? DB::connection())->unprepared((string) file_get_contents($path));
    }
}
