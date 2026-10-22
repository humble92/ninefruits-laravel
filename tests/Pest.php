<?php

use App\Domain\Security\Settings\SecuritySettings;
use Database\Factories\UserFactory;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use PragmaRX\Google2FA\Google2FA;
use Tests\DatabaseTestCase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

pest()->extend(DatabaseTestCase::class)->in('Feature', 'Database');

pest()->extend(TestCase::class)->in('Unit');

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
*/

/**
 * Runs a query inside a savepoint so that an expected database error does not abort
 * the surrounding test transaction. Returns the error message, or null when it succeeded.
 */
function databaseError(Closure $callback): ?string
{
    try {
        DB::transaction($callback);
    } catch (QueryException $exception) {
        return $exception->getMessage();
    }

    return null;
}

function currentOtp(string $secret = UserFactory::TWO_FACTOR_TEST_SECRET): string
{
    return app(Google2FA::class)->getCurrentOtp($secret);
}

/**
 * @param  array<string, mixed>  $values
 */
function setSecuritySettings(array $values): void
{
    $settings = app(SecuritySettings::class);
    $settings->fill($values)->save();
}
