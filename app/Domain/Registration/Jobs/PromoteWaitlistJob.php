<?php

namespace App\Domain\Registration\Jobs;

use App\Domain\Registration\Actions\PromoteWaitlist;
use App\Models\Offering\Section;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Promotes a section's waitlist after a seat is freed. One pending job per section is
 * enough (it serves every free seat).
 */
class PromoteWaitlistJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $uniqueFor = 60;

    public function __construct(public readonly int $sectionId) {}

    public function uniqueId(): string
    {
        return (string) $this->sectionId;
    }

    public function handle(PromoteWaitlist $promote): void
    {
        $section = Section::query()->find($this->sectionId);

        if ($section !== null) {
            $promote->handle($section);
        }
    }
}
