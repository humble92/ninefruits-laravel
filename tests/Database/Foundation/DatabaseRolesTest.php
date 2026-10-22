<?php

use Illuminate\Support\Facades\DB;

it('connects the application with the runtime role, not the schema owner', function () {
    expect(DB::scalar('SELECT current_user'))->toBe(config('database.roles.runtime'))
        ->and(DB::scalar('SELECT rolbypassrls FROM pg_roles WHERE rolname = current_user'))->toBeFalse();
});

it('creates the application schemas', function () {
    $schemas = collect(DB::select('SELECT nspname FROM pg_namespace'))->pluck('nspname');

    expect($schemas)->toContain('audit', 'reporting', 'util');
});

it('does not let the runtime role change the schema', function () {
    expect(databaseError(fn () => DB::statement('CREATE TABLE public.not_allowed (id int)')))
        ->toContain('permission denied');
});

it('runs migration commands on the migrator connection', function () {
    expect(DB::connection(config('database.migrator_connection'))->scalar('SELECT current_user'))
        ->toBe('app_migrator')
        ->and(DB::connection(config('database.migrator_connection'))->scalar("SELECT tableowner FROM pg_tables WHERE tablename = 'users'"))
        ->toBe('app_migrator');
});
