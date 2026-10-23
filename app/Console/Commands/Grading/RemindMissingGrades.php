<?php

namespace App\Console\Commands\Grading;

use App\Console\Commands\AuditedCommand;
use App\Domain\Grading\Enums\GradeStatus;
use App\Domain\Grading\Notifications\MissingGradesNotification;
use App\Domain\Registration\Enums\EnrollmentStatus;
use App\Models\Offering\Section;
use App\Models\Person\Instructor;
use App\Models\Term\Term;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;

#[Signature('grades:remind-missing {term : Term code} {--dry-run : List the instructors without sending}')]
#[Description('Email instructors whose sections still have grades not submitted')]
class RemindMissingGrades extends AuditedCommand
{
    public function handle(): int
    {
        $term = Term::query()->where('code', (string) $this->argument('term'))->first();

        if ($term === null) {
            $this->components->error('Unknown term code.');

            return self::FAILURE;
        }

        $sections = Section::query()->where('term_id', $term->id)
            ->whereHas('enrollments', fn ($query) => $query->where('status', EnrollmentStatus::Enrolled)->whereIn('grade_status', [GradeStatus::None, GradeStatus::Draft]))
            ->with(['course', 'version', 'instructors.person.user'])->get();
        $byInstructor = $sections->flatMap(fn (Section $section) => $section->instructors->map(fn (Instructor $instructor) => [$instructor, $section]))
            ->groupBy(fn (array $pair) => $pair[0]->id);
        $sent = 0;

        foreach ($byInstructor as $pairs) {
            /** @var Instructor $instructor */
            $instructor = $pairs[0][0];
            $mine = $pairs->map(fn (array $pair) => $pair[1])->values();
            $this->line($instructor->person->displayName().': '.$mine->map(fn (Section $section) => $section->label())->implode(', '));

            if (! $this->isDryRun() && $instructor->person->user !== null) {
                $instructor->person->user->notify(new MissingGradesNotification($mine));
                $sent++;
            }
        }

        $this->recordResult(['sections' => $sections->count(), 'instructors' => $byInstructor->count(), 'sent' => $sent]);
        $this->components->info("{$sections->count()} section(s) with missing grades, {$byInstructor->count()} instructor(s), {$sent} reminder(s) sent.");

        return self::SUCCESS;
    }
}
