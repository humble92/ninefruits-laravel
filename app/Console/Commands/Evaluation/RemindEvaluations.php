<?php

namespace App\Console\Commands\Evaluation;

use App\Console\Commands\AuditedCommand;
use App\Domain\Evaluation\Notifications\EvaluationReminderNotification;
use App\Domain\Evaluation\Services\EvaluationTasks;
use App\Domain\Registration\Enums\EnrollmentStatus;
use App\Models\Person\StudentRecord;
use App\Models\Registration\Enrollment;
use App\Models\Term\Term;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;

#[Signature('evaluation:remind {term : Term code} {--dry-run : Count the students without sending}')]
#[Description('Email students who have course evaluations open and not done')]
class RemindEvaluations extends AuditedCommand
{
    public function handle(EvaluationTasks $tasks): int
    {
        $term = Term::query()->where('code', (string) $this->argument('term'))->first();

        if ($term === null) {
            $this->components->error('Unknown term code.');

            return self::FAILURE;
        }

        $students = 0;
        $sent = 0;
        $recordIds = Enrollment::query()->where('term_id', $term->id)->whereIn('status', [EnrollmentStatus::Enrolled, EnrollmentStatus::Completed])
            ->distinct()->pluck('student_record_id');

        foreach (StudentRecord::query()->whereIn('id', $recordIds)->with('person.user')->lazyById() as $record) {
            $pending = $tasks->pending($record)->filter(fn (Enrollment $enrollment) => $enrollment->term_id === $term->id)->values();

            if ($pending->isEmpty()) {
                continue;
            }

            $students++;

            if (! $this->isDryRun() && $record->person->user !== null) {
                $record->person->user->notify(new EvaluationReminderNotification($pending));
                $sent++;
            }
        }

        $this->recordResult(['students' => $students, 'sent' => $sent]);
        $this->components->info("{$students} student(s) with evaluations to do, {$sent} reminder(s) sent.");

        return self::SUCCESS;
    }
}
