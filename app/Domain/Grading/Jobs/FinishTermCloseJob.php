<?php

namespace App\Domain\Grading\Jobs;

use App\Domain\Grading\Actions\CloseTerm;
use App\Models\Term\Term;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Last step of term close, after every student's record: honors ranks, the records become
 * the official snapshot and the term moves to grades closed.
 */
class FinishTermCloseJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $termId,
        public readonly ?int $userId,
        public readonly ?string $reason,
    ) {}

    public function handle(CloseTerm $close): void
    {
        $close->finish(Term::query()->findOrFail($this->termId), $this->userId === null ? null : User::query()->find($this->userId), $this->reason);
    }
}
