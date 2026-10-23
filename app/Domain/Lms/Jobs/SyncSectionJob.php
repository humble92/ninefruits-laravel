<?php

namespace App\Domain\Lms\Jobs;

use App\Domain\Lms\Actions\SyncSection;
use App\Models\Lms\LmsPlatform;
use App\Models\Offering\Section;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Pushes one section to one platform. Retried by the queue; each step is logged.
 */
class SyncSectionJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public readonly int $platformId, public readonly int $sectionId) {}

    public function handle(SyncSection $sync): void
    {
        $platform = LmsPlatform::query()->find($this->platformId);
        $section = Section::query()->find($this->sectionId);

        if ($platform !== null && $section !== null && $platform->is_active) {
            $sync->handle($platform, $section);
        }
    }
}
