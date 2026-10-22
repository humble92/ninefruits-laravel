<?php

namespace App\Console\Commands\Offering;

use App\Console\Commands\AuditedCommand;
use App\Domain\Offering\Actions\CopySections;
use App\Models\Organization\OrgUnit;
use App\Models\Term\Term;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;

#[Signature('section:copy
    {from-term : Code of the term to copy from, e.g. 2026-FA}
    {to-term : Code of the term to copy into}
    {--org-unit=* : Only sections offered by these unit codes}
    {--dry-run : Show what would be copied without saving}')]
#[Description('Copy sections (instructors, meetings, rooms, seat groups, syllabus drafts) from an earlier term. Requires --actor.')]
class CopySectionsCommand extends AuditedCommand
{
    public function handle(CopySections $copy): int
    {
        $from = Term::query()->where('code', (string) $this->argument('from-term'))->first();
        $to = Term::query()->where('code', (string) $this->argument('to-term'))->first();
        $actor = $this->actor();

        if ($from === null || $to === null) {
            $this->components->error('Unknown term code.');

            return self::FAILURE;
        }

        if ($actor === null) {
            $this->components->error('--actor is required: sections are copied within that account\'s scope.');

            return self::FAILURE;
        }

        /** @var list<string> $units */
        $units = (array) $this->option('org-unit');
        $unitIds = $units === [] ? null : OrgUnit::query()->whereIn('code', $units)->pluck('id')->all();

        $report = $copy->handle($from, $to, $actor, $unitIds, $this->isDryRun());
        $this->recordResult(['from' => $from->code, 'to' => $to->code, ...$report->summary()]);

        foreach ($report->skipped as $label => $reason) {
            $this->line("  <fg=yellow>skipped</> {$label}: {$reason}");
        }

        foreach ($report->notes as $label => $notes) {
            $this->line("  <fg=cyan>note</> {$label}: ".implode('; ', $notes));
        }

        $summary = $report->summary();
        $this->components->info(sprintf('%s%d section(s) copied, %d skipped, %d with notes.', $this->isDryRun() ? 'Dry run: ' : '', $summary['created'], $summary['skipped'], $summary['with_notes']));

        return self::SUCCESS;
    }
}
