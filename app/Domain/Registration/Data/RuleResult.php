<?php

namespace App\Domain\Registration\Data;

use App\Domain\Registration\Enums\RuleCode;
use App\Domain\Registration\Enums\RuleSeverity;

/**
 * One rule's result as configured: a failed rule blocks unless it only warns or was
 * overridden.
 */
final readonly class RuleResult
{
    /**
     * @param  array<string, mixed>  $details
     */
    public function __construct(
        public RuleCode $code,
        public bool $passed,
        public RuleSeverity $severity,
        public bool $overridden,
        public ?string $message,
        public array $details = [],
    ) {}

    public function blocks(): bool
    {
        return ! $this->passed && ! $this->overridden && $this->severity === RuleSeverity::Block;
    }

    /**
     * @return array{code: string, passed: bool, severity: string, overridden: bool, message: string|null}
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code->value,
            'passed' => $this->passed,
            'severity' => $this->severity->value,
            'overridden' => $this->overridden,
            'message' => $this->message,
        ];
    }
}
