<?php

use Illuminate\Contracts\Console\Kernel;

/*
| Every application command must be described in the operations manual
| (docs/porting-plan.md section 13.3).
*/

it('documents every application command in the operations manual', function () {
    $manual = (string) file_get_contents(base_path('docs/manual/operations/commands.md'));

    $commands = collect(app(Kernel::class)->all())
        ->filter(fn ($command) => str_starts_with($command::class, 'App\\'))
        ->keys();

    expect($commands)->not->toBeEmpty();

    $undocumented = $commands->reject(fn (string $name) => str_contains($manual, "### `{$name}`"))->values()->all();

    expect($undocumented)->toBe([]);
});
