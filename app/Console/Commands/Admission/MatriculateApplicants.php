<?php

namespace App\Console\Commands\Admission;

use App\Console\Commands\AuditedCommand;
use App\Domain\Admission\Actions\MatriculateApplicant;
use App\Domain\Admission\Enums\ApplicationStatus;
use App\Models\Admission\AdmissionCycle;
use App\Models\Admission\Application;
use App\Models\Term\Term;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Validation\ValidationException;

/**
 * Bulk matriculation of confirmed applicants, the same as the admin screen's bulk action.
 * Each applicant is done in its own transaction, so one failure does not stop the rest.
 */
#[Signature('admission:matriculate
    {cycle : Admission cycle code}
    {--term= : Admission term code, e.g. 2027-SP (required)}
    {--dry-run : List the applicants without creating student records}')]
#[Description('Create student records for confirmed applicants of a cycle. Requires --term and --actor.')]
class MatriculateApplicants extends AuditedCommand
{
    public function handle(MatriculateApplicant $matriculate): int
    {
        $cycle = AdmissionCycle::query()->where('code', (string) $this->argument('cycle'))->first();
        $term = Term::query()->where('code', (string) $this->option('term'))->first();
        $actor = $this->actor();

        if ($cycle === null || $term === null) {
            $this->components->error('Unknown cycle or --term.');

            return self::FAILURE;
        }

        if ($actor === null) {
            $this->components->error('--actor is required: matriculation is recorded under that account and limited to its scope.');

            return self::FAILURE;
        }

        $applications = Application::query()->where('admission_cycle_id', $cycle->id)
            ->where('status', ApplicationStatus::Confirmed)->whereNull('student_record_id')
            ->with(['person', 'program', 'cycle'])->orderBy('application_number')->get();

        $this->recordResult(['cycle' => $cycle->code, 'term' => $term->code, 'candidates' => $applications->count()]);

        if ($this->isDryRun()) {
            foreach ($applications as $application) {
                $this->line("  {$application->application_number}  {$application->person->romanizedName()}  {$application->program->code}");
            }

            $this->components->info(sprintf('Dry run: %d confirmed applicant(s) would be matriculated into %s.', $applications->count(), $term->code));

            return self::SUCCESS;
        }

        $created = [];
        $failed = [];

        foreach ($applications as $application) {
            try {
                $record = $matriculate->handle($application, $term, $actor);
                $created[] = $record->student_number;
                $this->line("  <fg=green>created</> {$application->application_number} → {$record->student_number}");
            } catch (ValidationException|AuthorizationException $exception) {
                $failed[] = $application->application_number;
                $this->line("  <fg=red>failed</> {$application->application_number}: ".($exception instanceof ValidationException ? collect($exception->errors())->flatten()->first() : 'not in your scope'));
            }
        }

        $this->recordResult(['created' => count($created), 'failed' => $failed]);
        $this->components->info(sprintf('%d student record(s) created, %d failed.', count($created), count($failed)));

        return $failed === [] ? self::SUCCESS : self::PROBLEMS_FOUND;
    }
}
