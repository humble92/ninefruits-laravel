<?php

namespace App\Domain\Lms\Jobs;

use App\Domain\Lms\Actions\SyncEnrollment;
use App\Models\Lms\LmsPlatform;
use App\Models\Registration\Enrollment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Adds or removes one enrollment on one platform.
 */
class SyncEnrollmentJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public readonly int $platformId, public readonly int $enrollmentId, public readonly bool $removed = false) {}

    public function handle(SyncEnrollment $sync): void
    {
        $platform = LmsPlatform::query()->find($this->platformId);
        $enrollment = Enrollment::query()->find($this->enrollmentId);

        if ($platform === null || $enrollment === null || ! $platform->is_active) {
            return;
        }

        $this->removed ? $sync->removed($platform, $enrollment) : $sync->added($platform, $enrollment);
    }
}
