<?php

namespace App\Domain\Lms\Listeners;

use App\Domain\Grading\Events\GradesFinalized;
use App\Domain\Lms\Jobs\SyncEnrollmentJob;
use App\Domain\Lms\Jobs\SyncSectionJob;
use App\Domain\Offering\Events\SectionPublished;
use App\Domain\Registration\Events\EnrollmentConfirmed;
use App\Domain\Registration\Events\EnrollmentDropped;
use App\Models\Lms\LmsPlatform;
use Illuminate\Database\Eloquent\Collection;

/**
 * Turns domain events into synchronization jobs for every active platform
 * (porting/porting-plan.md section 12). Finalized grades are not pushed: the academic system
 * is their source and the LMS keeps its own raw scores.
 */
class QueueLmsSync
{
    public function handleSectionPublished(SectionPublished $event): void
    {
        foreach ($this->platforms() as $platform) {
            SyncSectionJob::dispatch($platform->id, $event->section->id)->afterCommit();
        }
    }

    public function handleEnrollmentConfirmed(EnrollmentConfirmed $event): void
    {
        foreach ($this->platforms() as $platform) {
            if ($platform->should_sync_enrollments) {
                SyncEnrollmentJob::dispatch($platform->id, $event->enrollment->id)->afterCommit();
            }
        }
    }

    public function handleEnrollmentDropped(EnrollmentDropped $event): void
    {
        foreach ($this->platforms() as $platform) {
            if ($platform->should_sync_enrollments) {
                SyncEnrollmentJob::dispatch($platform->id, $event->enrollment->id, removed: true)->afterCommit();
            }
        }
    }

    public function handleGradesFinalized(GradesFinalized $event): void
    {
        // Nothing to push; kept so the subscription shows every event the integration sees.
    }

    /**
     * @return array<string, string>
     */
    public function subscribe(): array
    {
        return [
            SectionPublished::class => 'handleSectionPublished',
            EnrollmentConfirmed::class => 'handleEnrollmentConfirmed',
            EnrollmentDropped::class => 'handleEnrollmentDropped',
            GradesFinalized::class => 'handleGradesFinalized',
        ];
    }

    /**
     * @return Collection<int, LmsPlatform>
     */
    private function platforms()
    {
        return LmsPlatform::query()->where('is_active', true)->get();
    }
}
