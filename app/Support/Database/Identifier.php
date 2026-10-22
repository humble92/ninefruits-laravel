<?php

namespace App\Support\Database;

/**
 * Quotes PostgreSQL identifiers (role, schema, table names) taken from configuration.
 */
final class Identifier
{
    public static function quote(string $name): string
    {
        return '"'.str_replace('"', '""', $name).'"';
    }

    public static function runtimeRole(): string
    {
        return self::quote((string) config('database.roles.runtime'));
    }

    public static function reportingRole(): string
    {
        return self::quote((string) config('database.roles.reporting'));
    }
}
