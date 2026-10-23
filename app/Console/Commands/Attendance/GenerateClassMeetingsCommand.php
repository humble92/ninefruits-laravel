<?php

namespace App\Console\Commands\Attendance;

use App\Console\Commands\AuditedCommand;
use App\Domain\Attendance\Actions\GenerateClassMeetings;
use App\Domain\Offering\Enums\SectionStatus;
use App\Models\Offering\Section;
use App\Models\Term\Term;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;

#[Signature('attendance:generate-sessions {term : Term code} {--section= : Only this section (course code-section code, e.g. CSE2010-01)} {--dry-run : Count only}')]
#[Description('Create the class meetings of a term from the sections\' weekly meeting times (existing meetings are kept)')]
class GenerateClassMeetingsCommand extends AuditedCommand
{
    public function handle(GenerateClassMeetings $generate): int
    {
        $term = Term::query()->where('code', (string) $this->argument('term'))->first();

        if ($term === null) {
            $this->components->error('Unknown term code.');

            return self::FAILURE;
        }

        $sections = Section::query()->where('term_id', $term->id)->where('status', '!=', SectionStatus::Cancelled)->with(['course', 'meetings']);

        if ($this->option('section')) {
            [$courseCode, $sectionCode] = array_pad(explode('-', (string) $this->option('section'), 2), 2, null);
            $sections->whereHas('course', fn ($q) => $q->where('code', $courseCode))->when($sectionCode, fn ($q) => $q->where('section_code', $sectionCode));
        }

        if ($this->isDryRun()) {
            $count = $sections->count();
            $this->components->info("Dry run: {$count} section(s) would be processed.");

            return self::SUCCESS;
        }

        $summary = ['sections' => 0, 'created' => 0];

        foreach ($sections->cursor() as $section) {
            $summary['created'] += $generate->forSection($section, null);
            $summary['sections']++;
        }

        $this->recordResult(['term' => $term->code, ...$summary]);
        $this->components->info("{$summary['sections']} section(s): {$summary['created']} meeting(s) created.");

        return self::SUCCESS;
    }
}
