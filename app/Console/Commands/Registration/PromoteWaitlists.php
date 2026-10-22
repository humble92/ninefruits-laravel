<?php

namespace App\Console\Commands\Registration;

use App\Console\Commands\AuditedCommand;
use App\Domain\Registration\Actions\PromoteWaitlist;
use App\Domain\Registration\Enums\WaitlistStatus;
use App\Models\Offering\Section;
use App\Models\Registration\WaitlistEntry;
use App\Models\Term\Term;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;

/**
 * Normally promotion runs by itself when a seat is freed. This serves every waitlist (of a
 * term) now: after a capacity increase, and on schedule to expire lists whose windows have
 * closed.
 */
#[Signature('waitlist:promote {term? : Term code; default: every term with waiting students}')]
#[Description('Promote waiting students to free seats (rules checked again); expire waitlists of closed windows')]
class PromoteWaitlists extends AuditedCommand
{
    public function handle(PromoteWaitlist $promote): int
    {
        $termId = $this->argument('term') ? Term::query()->where('code', (string) $this->argument('term'))->value('id') : null;

        if ($this->argument('term') && $termId === null) {
            $this->components->error('Unknown term code.');

            return self::FAILURE;
        }

        $sections = Section::query()->whereIn('id', WaitlistEntry::query()->where('status', WaitlistStatus::Waiting)->select('section_id'))
            ->when($termId !== null, fn ($query) => $query->where('term_id', $termId))->get();
        $totals = ['sections' => $sections->count(), 'promoted' => 0, 'removed' => 0, 'expired' => 0];

        foreach ($sections as $section) {
            foreach ($promote->handle($section) as $key => $count) {
                $totals[$key] += $count;
            }
        }

        $this->recordResult($totals);
        $this->components->info(sprintf('%d section(s): %d promoted, %d removed, %d expired.', $totals['sections'], $totals['promoted'], $totals['removed'], $totals['expired']));

        return self::SUCCESS;
    }
}
