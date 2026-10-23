<?php

namespace App\Domain\Grading\Data;

use App\Models\Offering\Section;
use App\Models\Term\Term;
use Illuminate\Support\Collection;

/**
 * What stands between a term and its close: sections with registered students whose grades
 * are not finalized yet.
 */
final readonly class TermClosePreflight
{
    /**
     * @param  Collection<int, Section>  $openSections  with unfinished_count
     */
    public function __construct(
        public Term $term,
        public Collection $openSections,
        public int $students,
    ) {}

    public function isReady(): bool
    {
        return $this->openSections->isEmpty();
    }
}
