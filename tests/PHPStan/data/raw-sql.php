<?php

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

function rawSqlExamples(Builder $query, ConnectionInterface $connection, string $input, int $id): void
{
    DB::select('SELECT * FROM users WHERE id = ?', [$id]);
    $query->whereRaw('lower(email) = ?', [$input]);
    $query->orderByRaw('created_at DESC');
    $connection->statement('SET app.locale = ?', [$input]);

    DB::select("SELECT * FROM users WHERE email = '{$input}'");
    $query->whereRaw('email = '.$input);
    $query->orderByRaw($input);
    $connection->unprepared($input);
    DB::raw("lower({$input})");

    $query->select(['id', $input]);
    $query->update(['name' => $input]);
}
