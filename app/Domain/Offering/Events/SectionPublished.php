<?php

namespace App\Domain\Offering\Events;

use App\Models\Offering\Section;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A section became published (porting/porting-plan.md section 12: the LMS course is created).
 */
final class SectionPublished
{
    use Dispatchable;

    public function __construct(public readonly Section $section) {}
}
