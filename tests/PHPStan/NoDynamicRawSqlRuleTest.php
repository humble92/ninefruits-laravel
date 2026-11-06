<?php

namespace Tests\PHPStan;

use PHPStan\Rules\Rule;
use Tools\PHPStan\Rules\NoDynamicRawSqlRule;

/**
 * @extends LarastanRuleTestCase<NoDynamicRawSqlRule>
 */
class NoDynamicRawSqlRuleTest extends LarastanRuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoDynamicRawSqlRule;
    }

    public function test_it_reports_raw_sql_built_from_variables(): void
    {
        $message = fn (string $method) => "Raw SQL passed to {$method}() is not a constant string. Use bindings for values, or an allow list for identifiers.";

        $this->analyse([__DIR__.'/data/raw-sql.php'], [
            [$message('select'), 14],
            [$message('whereRaw'), 15],
            [$message('orderByRaw'), 16],
            [$message('unprepared'), 17],
            [$message('raw'), 18],
        ]);
    }
}
