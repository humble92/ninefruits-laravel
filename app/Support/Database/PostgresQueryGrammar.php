<?php

namespace App\Support\Database;

use Illuminate\Database\Query\Grammars\PostgresGrammar;

/**
 * Sends dates with their UTC offset. Laravel's default format (Y-m-d H:i:s) drops the
 * offset, so a time in the institution's time zone would be stored in timestamptz columns
 * and compared in queries as if it were UTC. Date columns still keep the local date.
 */
class PostgresQueryGrammar extends PostgresGrammar
{
    public function getDateFormat(): string
    {
        return 'Y-m-d H:i:sP';
    }
}
