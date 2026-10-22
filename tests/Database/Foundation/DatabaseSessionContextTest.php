<?php

use App\Support\Database\DatabaseSessionContext;
use Illuminate\Support\Facades\DB;

it('does not connect until the first query', function () {
    config(['database.connections.unused' => array_merge(config('database.connections.pgsql'), ['database' => 'does_not_exist'])]);
    config(['database.default' => 'unused']);

    $context = new DatabaseSessionContext(app('db'));
    $context->forSystem('command:key:generate');
    $context->reset();

    expect(DB::connection('unused')->getRawPdo())->not->toBeInstanceOf(PDO::class);

    config(['database.default' => 'pgsql']);
});
