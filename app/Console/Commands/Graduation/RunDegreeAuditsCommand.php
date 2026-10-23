<?php

namespace App\Console\Commands\Graduation;

use App\Console\Commands\AuditedCommand;
use App\Domain\Graduation\Actions\RunDegreeAudits;
use App\Models\Term\Term;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;

#[Signature('degree-audit:run {--term= : Term code the audits belong to} {--student-number= : One student} {--min-year-level= : Only students at or above this year level}')]
#[Description('Store a degree audit for every enrolled student (or one), as the graduation screens read it')]
class RunDegreeAuditsCommand extends AuditedCommand
{
    public function handle(RunDegreeAudits $audits): int
    {
        $term = null;

        if ($this->option('term')) {
            $term = Term::query()->where('code', (string) $this->option('term'))->first();

            if ($term === null) {
                $this->components->error('Unknown term code.');

                return self::FAILURE;
            }
        }

        $summary = $audits->forAll($term, $this->actor(), $this->option('min-year-level') ? (int) $this->option('min-year-level') : null, $this->option('student-number') ?: null);
        $this->recordResult($summary);
        $this->components->info("{$summary['students']} student(s) audited, {$summary['satisfied']} satisfied.");

        return self::SUCCESS;
    }
}
