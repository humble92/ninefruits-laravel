<?php

namespace App\Domain\Grading\Events;

use App\Models\Offering\Section;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A section's grades were finalized (docs/porting-plan.md section 12: LMS and other
 * integrations listen to it).
 */
final class GradesFinalized
{
    use Dispatchable;

    /**
     * @param  list<int>  $enrollmentIds
     */
    public function __construct(
        public readonly Section $section,
        public readonly array $enrollmentIds,
    ) {}
}
