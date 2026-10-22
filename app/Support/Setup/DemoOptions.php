<?php

namespace App\Support\Setup;

use RuntimeException;

/**
 * Options for the demo seeders, bound in the container by setup:demo.
 */
final readonly class DemoOptions
{
    public function __construct(
        public string $size = 'small',
        public ?int $seed = null,
    ) {}

    public function studentCount(): int
    {
        return (int) (config("setup.demo.sizes.{$this->size}") ?? throw new RuntimeException("Unknown demo size [{$this->size}]."));
    }

    /**
     * Scales a small-size count to the chosen size.
     */
    public function scale(int $smallCount): int
    {
        return max(1, intdiv($smallCount * $this->studentCount(), (int) config('setup.demo.sizes.small')));
    }

    public static function ensureNotProduction(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('Demo data cannot be created in production.');
        }
    }
}
