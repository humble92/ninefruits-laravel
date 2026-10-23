<?php

namespace App\Console\Commands\Assessment;

use App\Console\Commands\AuditedCommand;
use App\Domain\Assessment\Services\AssessmentResultImporter;
use App\Models\Assessment\AssessmentSession;
use App\Models\Assessment\AssessmentType;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;

#[Signature('assessment:import-results {type : Kind code} {held_date : Session date (YYYY-MM-DD)} {file : CSV with student_number and a column per area code} {--dry-run : Check the rows without recording}')]
#[Description('Record the results of a session from a CSV (same rules as the admin screen). Requires --actor.')]
class ImportAssessmentResults extends AuditedCommand
{
    public function handle(AssessmentResultImporter $importer): int
    {
        $type = AssessmentType::query()->where('code', (string) $this->argument('type'))->first();
        $session = $type === null ? null : AssessmentSession::query()->where('assessment_type_id', $type->id)->whereDate('held_date', (string) $this->argument('held_date'))->first();
        $path = (string) $this->argument('file');
        $actor = $this->actor();

        if ($session === null) {
            $this->components->error('Unknown session: give the kind code and the session date.');

            return self::FAILURE;
        }

        if (! is_readable($path)) {
            $this->components->error("Cannot read [{$path}].");

            return self::FAILURE;
        }

        if ($actor === null) {
            $this->components->error('--actor is required: results are recorded under that account.');

            return self::FAILURE;
        }

        $summary = $importer->import($session, $path, $actor, $this->isDryRun());
        $this->recordResult(['session_id' => $session->id, 'file' => basename($path), 'rows' => $summary['rows'], 'recorded' => $summary['recorded'], 'absent' => $summary['absent'], 'errors' => count($summary['errors'])]);

        foreach ($summary['errors'] as $error) {
            $this->components->warn($error);
        }

        $this->components->info(sprintf('%s%d row(s): %d recorded, %d absent, %d error(s).', $this->isDryRun() ? 'Dry run: ' : '', $summary['rows'], $summary['recorded'], $summary['absent'], count($summary['errors'])));

        return $summary['errors'] === [] ? self::SUCCESS : self::PROBLEMS_FOUND;
    }
}
