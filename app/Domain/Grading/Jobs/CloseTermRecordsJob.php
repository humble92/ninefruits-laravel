<?php

namespace App\Domain\Grading\Jobs;

use App\Domain\Grading\Actions\CloseTerm;
use App\Models\Term\Term;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Term close for a chunk of students: term records, standing and honors eligibility.
 */
class CloseTermRecordsJob implements ShouldQueue
{
    use Batchable, Queueable;

    /**
     * @param  list<int>  $studentRecordIds
     */
    public function __construct(
        public readonly int $termId,
        public readonly array $studentRecordIds,
    ) {}

    public function handle(CloseTerm $close): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $close->closeRecords(Term::query()->with('type')->findOrFail($this->termId), $this->studentRecordIds);
    }
}
