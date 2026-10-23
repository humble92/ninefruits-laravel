<?php

namespace App\Console\Commands\Grading;

use App\Console\Commands\AuditedCommand;
use App\Domain\Grading\Actions\RecalculateTermRecords;
use App\Models\Person\StudentRecord;
use App\Models\Term\Term;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Validation\ValidationException;

#[Signature('grades:recalculate {--term= : Term code: students with grades in it, from that term on} {--student-number= : One student} {--reason= : Why (required)}')]
#[Description('Recalculate term records (credits and GPA) from finalized grades, e.g. after a grading policy change')]
class RecalculateGrades extends AuditedCommand
{
    public function handle(RecalculateTermRecords $recalculate): int
    {
        $term = $this->option('term') ? Term::query()->where('code', (string) $this->option('term'))->first() : null;
        $record = $this->option('student-number') ? StudentRecord::query()->where('student_number', (string) $this->option('student-number'))->first() : null;

        if (($this->option('term') && $term === null) || ($this->option('student-number') && $record === null)) {
            $this->components->error('Unknown term code or student number.');

            return self::FAILURE;
        }

        try {
            $count = $recalculate->handle($term, $record, (string) $this->option('reason'), $this->actor());
        } catch (ValidationException) {
            $this->components->error('--reason is required.');

            return self::FAILURE;
        }

        $this->recordResult(['students' => $count]);
        $this->components->info("{$count} student(s) recalculated. Standing and honors stay as decided at term close.");

        return self::SUCCESS;
    }
}
