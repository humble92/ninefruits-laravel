<?php

use App\Console\Commands\Naming\CheckNaming;

function namingProblems(string $schema): array
{
    return (new CheckNaming)->check($schema);
}

it('accepts names that follow the conventions', function () {
    expect(namingProblems(<<<'PHP'
        Schema::create('course_versions', function (Blueprint $table) {
            $table->id();
            $table->jsonb('title');
            $table->boolean('counts_toward_credit_limit');
            $table->boolean('is_retake');
            $table->date('birth_date');
            $table->timestampTz('finalized_at');
            $table->timestampsTz();
        });
        PHP))->toBe([]);
});

it('rejects legacy style names', function (string $schema, string $expected) {
    expect(implode("\n", namingProblems($schema)))->toContain($expected);
})->with([
    'language code column' => ["\$table->string('name_kr');", 'language code'],
    'language prefix' => ["\$table->string('en_name');", 'language code'],
    'backup table' => ["Schema::create('scores_backup', fn () => null);", 'forbidden suffix'],
    'date suffix table' => ["Schema::create('scores_20170302', fn () => null);", 'forbidden suffix'],
    'numbered column' => ["\$table->string('score2');", 'forbidden suffix'],
    'upper case' => ["\$table->string('SFOP_MAX');", 'snake_case'],
    'timestamp without zone' => ["\$table->timestamp('dropped_at');", 'Tz variant'],
    'timestamps without zone' => ['$table->timestamps();', 'timestampsTz'],
    'boolean without prefix' => ["\$table->boolean('retake');", 'boolean column'],
    'date without suffix' => ["\$table->date('birthday');", '_date'],
]);

it('passes for the application migrations', function () {
    $this->artisan('naming:check')->assertSuccessful();
});
