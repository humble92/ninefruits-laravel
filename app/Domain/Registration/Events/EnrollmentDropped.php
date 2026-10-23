<?php

namespace App\Domain\Registration\Events;

use App\Models\Registration\Enrollment;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * An enrollment ended before completion (dropped or withdrawn).
 */
final class EnrollmentDropped
{
    use Dispatchable;

    public function __construct(public readonly Enrollment $enrollment) {}
}
