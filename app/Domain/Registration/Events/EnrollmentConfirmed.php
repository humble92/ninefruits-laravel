<?php

namespace App\Domain\Registration\Events;

use App\Models\Registration\Enrollment;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A student is enrolled in a section (registration, waitlist promotion, staff enrollment).
 */
final class EnrollmentConfirmed
{
    use Dispatchable;

    public function __construct(public readonly Enrollment $enrollment) {}
}
