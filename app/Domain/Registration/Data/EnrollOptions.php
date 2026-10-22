<?php

namespace App\Domain\Registration\Data;

use App\Domain\Registration\Enums\EnrollmentSource;
use App\Domain\Registration\Enums\RuleCode;

/**
 * How an enroll attempt runs. Staff may waive rules that allow overrides, with a reason.
 */
final readonly class EnrollOptions
{
    /**
     * @param  list<RuleCode>  $waive
     */
    public function __construct(
        public bool $waitlistIfFull = false,
        public array $waive = [],
        public ?string $reason = null,
        public ?EnrollmentSource $source = null,
    ) {}
}
