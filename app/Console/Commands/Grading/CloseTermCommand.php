<?php

namespace App\Console\Commands\Grading;

use App\Console\Commands\AuditedCommand;
use App\Domain\Grading\Actions\CloseTerm;
use App\Domain\Term\Enums\TermStatus;
use App\Models\Offering\Section;
use App\Models\Term\Term;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;

#[Signature('term:close {term : Term code, e.g. 2026-FA} {--dry-run : Only check whether the term can close} {--reason= : Why (recorded with the close)}')]
#[Description('Close a term: term records, academic standing, honors, then grades closed (a queued batch)')]
class CloseTermCommand extends AuditedCommand
{
    public function handle(CloseTerm $close): int
    {
        $term = Term::query()->with('type')->where('code', (string) $this->argument('term'))->first();

        if ($term === null) {
            $this->components->error('Unknown term code.');

            return self::FAILURE;
        }

        $preflight = $close->preflight($term);
        $this->components->info("{$preflight->students} student(s) with finalized grades.");

        foreach ($preflight->openSections as $section) {
            /** @var Section $section */
            $this->components->warn("{$section->label()}: {$section->getAttribute('unfinished_count')} grade(s) not finalized.");
        }

        $this->recordResult(['students' => $preflight->students, 'open_sections' => $preflight->openSections->count()]);

        if ($term->status !== TermStatus::Published) {
            $this->components->error("The term is {$term->status->value}; only a published term closes.");

            return self::FAILURE;
        }

        if (! $preflight->isReady()) {
            return self::PROBLEMS_FOUND;
        }

        if ($this->isDryRun()) {
            $this->components->info('Ready to close.');

            return self::SUCCESS;
        }

        $close->close($term, $this->actor(), $this->option('reason') ?: null);
        $this->components->info('Close started. The term moves to grades closed when the batch finishes (see Horizon).');

        return self::SUCCESS;
    }
}
