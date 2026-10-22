<?php

namespace App\Domain\Registration\Actions;

use App\Domain\Registration\Jobs\PromoteWaitlistJob;
use App\Models\Offering\SectionSeatGroup;
use Carbon\CarbonImmutable;

/**
 * Reserved seats become open seats by themselves at releases_at (SeatAllocator). This
 * runs after that time to hand the freed seats to the waitlists (scheduled every 5
 * minutes).
 */
class ReleaseReservedSeats
{
    /**
     * @return list<int>
     */
    public function handle(?CarbonImmutable $at = null): array
    {
        $at ??= CarbonImmutable::now();
        $groups = SectionSeatGroup::query()->whereNull('release_processed_at')->whereNotNull('releases_at')->where('releases_at', '<=', $at)->get();
        $sections = $groups->pluck('section_id')->unique()->values()->all();

        SectionSeatGroup::query()->whereIn('id', $groups->modelKeys())->toBase()->update(['release_processed_at' => $at]);

        foreach ($sections as $sectionId) {
            PromoteWaitlistJob::dispatch($sectionId);
        }

        return $sections;
    }
}
