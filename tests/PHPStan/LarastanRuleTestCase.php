<?php

namespace Tests\PHPStan;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * Rule tests that analyse with the Larastan extension loaded.
 *
 * @template TRule of Rule
 *
 * @extends RuleTestCase<TRule>
 */
abstract class LarastanRuleTestCase extends RuleTestCase
{
    private mixed $errorHandler = null;

    private mixed $exceptionHandler = null;

    /**
     * On a fresh machine PHPStan reads its directory listing cache before writing it,
     * which PHPUnit reports as a warning. Start with an empty cache.
     */
    public static function setUpBeforeClass(): void
    {
        $cache = sys_get_temp_dir().'/phpstan-tests/cache/directory-listings.bin';

        if (! is_file($cache)) {
            @mkdir(dirname($cache), 0777, true);
            file_put_contents($cache, serialize([]));
        }

        parent::setUpBeforeClass();
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__.'/../../vendor/larastan/larastan/extension.neon'];
    }

    /**
     * Saved before parent::setUp(), which builds the PHPStan container and may already boot Larastan.
     */
    protected function setUp(): void
    {
        $this->errorHandler = get_error_handler();
        $this->exceptionHandler = get_exception_handler();
        parent::setUp();
    }

    /**
     * Larastan boots a Laravel application during the first analysis in a process, which
     * registers an error and an exception handler; later analyses register none. Remove only
     * what this test added: popping a fixed number would remove PHPUnit's own handlers in the
     * second test of a class, which PHPUnit marks risky.
     *
     * restore_*_handler() always returns true, so the loops also stop on an empty stack: if
     * the analysed code removed the handler saved in setUp, it never comes back to the top.
     */
    protected function tearDown(): void
    {
        while (get_error_handler() !== $this->errorHandler && get_error_handler() !== null) {
            restore_error_handler();
        }

        while (get_exception_handler() !== $this->exceptionHandler && get_exception_handler() !== null) {
            restore_exception_handler();
        }

        parent::tearDown();
    }
}
