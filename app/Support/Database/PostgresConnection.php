<?php

namespace App\Support\Database;

use Illuminate\Database\PostgresConnection as BasePostgresConnection;

/**
 * PostgreSQL connection with PostgresQueryGrammar (dates sent with their UTC offset).
 */
class PostgresConnection extends BasePostgresConnection
{
    protected function getDefaultQueryGrammar()
    {
        return new PostgresQueryGrammar($this);
    }
}
