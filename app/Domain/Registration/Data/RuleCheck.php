<?php

namespace App\Domain\Registration\Data;

/**
 * The outcome of one rule before configuration (severity, overrides) is applied.
 */
final readonly class RuleCheck
{
    /**
     * @param  array<string, mixed>  $details  Facts for the explanation (limits, conflicts ...)
     */
    private function __construct(
        public bool $passed,
        public ?string $message = null,
        public array $details = [],
    ) {}

    /**
     * @param  array<string, mixed>  $details
     */
    public static function pass(?string $message = null, array $details = []): self
    {
        return new self(true, $message, $details);
    }

    /**
     * @param  array<string, mixed>  $details
     */
    public static function fail(string $message, array $details = []): self
    {
        return new self(false, $message, $details);
    }
}
