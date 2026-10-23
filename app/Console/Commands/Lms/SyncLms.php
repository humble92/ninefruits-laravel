<?php

namespace App\Console\Commands\Lms;

use App\Console\Commands\AuditedCommand;
use App\Domain\Lms\Actions\SyncSection;
use App\Domain\Offering\Enums\SectionStatus;
use App\Models\Lms\LmsPlatform;
use App\Models\Offering\Section;
use App\Models\Term\Term;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Throwable;

#[Signature('lms:sync {platform : Platform code} {--term= : Term code (default: terms not yet ended)} {--section= : One section, course code-section code} {--dry-run : Log what would be sent}')]
#[Description('Push published sections (course, instructors, enrolled students) to an LMS platform')]
class SyncLms extends AuditedCommand
{
    public function handle(SyncSection $sync): int
    {
        $platform = LmsPlatform::query()->where('code', (string) $this->argument('platform'))->first();

        if ($platform === null) {
            $this->components->error('Unknown platform code.');

            return self::FAILURE;
        }

        $sections = Section::query()->where('status', SectionStatus::Published)->with('course');

        if ($this->option('term')) {
            $term = Term::query()->where('code', (string) $this->option('term'))->first() ?? null;

            if ($term === null) {
                $this->components->error('Unknown term code.');

                return self::FAILURE;
            }

            $sections->where('term_id', $term->id);
        } else {
            $sections->whereHas('term', fn ($t) => $t->where('end_date', '>=', now()->toDateString()));
        }

        if ($this->option('section')) {
            [$courseCode, $sectionCode] = array_pad(explode('-', (string) $this->option('section'), 2), 2, null);
            $sections->whereHas('course', fn ($q) => $q->where('code', $courseCode))->when($sectionCode, fn ($q) => $q->where('section_code', $sectionCode));
        }

        $summary = ['sections' => 0, 'members' => 0, 'failed' => 0];

        foreach ($sections->cursor() as $section) {
            try {
                $result = $sync->handle($platform, $section, $this->isDryRun());
                $summary['sections']++;
                $summary['members'] += $result['members'];
            } catch (Throwable $e) {
                $summary['failed']++;
                $this->components->warn($section->label().': '.$e->getMessage());
            }
        }

        $this->recordResult(['platform' => $platform->code, ...$summary]);
        $this->components->info(sprintf('%s%d section(s), %d member(s), %d failed.', $this->isDryRun() ? 'Dry run: ' : '', $summary['sections'], $summary['members'], $summary['failed']));

        return $summary['failed'] === 0 ? self::SUCCESS : self::PROBLEMS_FOUND;
    }
}
