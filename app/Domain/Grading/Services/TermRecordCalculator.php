<?php

namespace App\Domain\Grading\Services;

use App\Domain\Grading\Data\GradedCourse;
use App\Domain\Grading\Enums\GradeStatus;
use App\Models\Grading\TermRecord;
use App\Models\Person\StudentRecord;
use App\Models\Registration\Enrollment;
use App\Models\Term\Term;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Credits and GPA of a student's term records from finalized grades. A grade change in an
 * earlier term changes every later cumulative value, so records are recalculated from a
 * term onwards. Standing, honors and the closed time are decided at term close and kept.
 */
class TermRecordCalculator
{
    public function __construct(private readonly GpaCalculator $gpa) {}

    /**
     * Recalculates the record's term records from the term on (every term without one).
     *
     * @return Collection<int, TermRecord> by term id
     */
    public function recalculate(StudentRecord $record, ?Term $from = null): Collection
    {
        $courses = Enrollment::query()->where('student_record_id', $record->id)->where('grade_status', GradeStatus::Finalized)
            ->with('term')->get()
            ->map(fn (Enrollment $enrollment) => GradedCourse::fromEnrollment($enrollment));
        $terms = Term::query()->whereIn('id', $courses->pluck('termId')->unique())
            ->when($from !== null, fn ($query) => $query->where('start_date', '>=', $from->start_date->toDateString()))
            ->orderBy('start_date')->get();
        $now = CarbonImmutable::now();
        $records = collect();

        foreach ($terms as $term) {
            $start = $term->start_date->toDateString();
            $inTerm = $courses->filter(fn (GradedCourse $course) => $course->termId === $term->id);
            $upTo = $courses->filter(fn (GradedCourse $course) => $course->termStart <= $start)->values()->all();
            $termTotals = $this->gpa->totals($inTerm);
            $cumulative = $this->gpa->cumulative($upTo);

            $records->put($term->id, TermRecord::query()->updateOrCreate(
                ['student_record_id' => $record->id, 'term_id' => $term->id],
                [
                    'attempted_credits' => $termTotals->attemptedCredits,
                    'earned_credits' => $termTotals->earnedCredits,
                    'gpa_credits' => $termTotals->gpaCredits,
                    'grade_points_total' => $termTotals->gradePointsTotal,
                    'term_gpa' => $termTotals->gpa,
                    'cumulative_attempted_credits' => $cumulative->attemptedCredits,
                    'cumulative_earned_credits' => $cumulative->earnedCredits,
                    'cumulative_gpa_credits' => $cumulative->gpaCredits,
                    'cumulative_grade_points_total' => $cumulative->gradePointsTotal,
                    'cumulative_gpa' => $cumulative->gpa,
                    'calculated_at' => $now,
                ],
            ));
        }

        return $records;
    }
}
