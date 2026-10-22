<?php

namespace App\Support\Setup;

/**
 * An optional bundle of rules applied at installation (database/seeders/Profiles).
 * Profile seeders are idempotent like essential seeders.
 */
interface SetupProfile
{
    public static function key(): string;

    public static function label(): string;

    public static function description(): string;
}
