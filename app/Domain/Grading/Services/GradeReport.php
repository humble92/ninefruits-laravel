<?php

namespace App\Domain\Grading\Services;

use App\Domain\Grading\Data\GradedCourse;
use App\Domain\Grading\Enums\GradeStatus;
use App\Domain\Registration\Enums\EnrollmentStatus;
use App\Models\Grading\TermRecord;
use App\Models\Person\StudentRecord;
use App\Models\Registration\Enrollment;
use App\Models\Registration\RegistrationHold;
use App\Models\Term\Term;
use Illuminate\Support\Collection;

/**
 * A student's grades by term for the grade view and the transcript (legacy stu/score.php):
 * finalized grades and withdrawals (W), the term records, and which attempts a retake
 * replaced in the cumulative GPA. Grades not finalized yet are not shown.
 */
class GradeReport
{
    public function __construct(private readonly GpaCalculator $gpa) {}

    /**
     * @param  list<int>  $hiddenTermIds  e.g. terms whose course evaluation the student has not finished
     * @return Collection<int, array{term: Term, enrollments: Collection<int, Enrollment>, record: TermRecord|null}> oldest term first
     */
    public function terms(StudentRecord $record, array $hiddenTermIds = []): Collection
    {
        $enrollments = Enrollment::query()->where('student_record_id', $record->id)->whereNotIn('term_id', $hiddenTermIds)
            ->where(fn ($query) => $query->where('grade_status', GradeStatus::Finalized)->orWhere('status', EnrollmentStatus::Withdrawn))
            ->with(['term.type', 'course', 'version', 'section'])
            ->get();
        $termRecords = TermRecord::query()->where('student_record_id', $record->id)->get()->keyBy('term_id');

        $terms = collect();

        foreach ($enrollments->groupBy('term_id') as $termId => $inTerm) {
            $terms->push([
                'term' => $inTerm->first()->term,
                'enrollments' => $inTerm->sortBy(fn (Enrollment $enrollment) => $enrollment->course->code)->values()->toBase(),
                'record' => $termRecords->get($termId),
            ]);
        }

        return $terms->sortBy(fn (array $term) => $term['term']->start_date->toDateString())->values();
    }

    /**
     * Enrollment ids a retake replaced in the cumulative GPA.
     *
     * @return array<int, true>
     */
    public function superseded(StudentRecord $record): array
    {
        $courses = Enrollment::query()->where('student_record_id', $record->id)->where('grade_status', GradeStatus::Finalized)->with('term')->get()
            ->map(fn (Enrollment $enrollment) => GradedCourse::fromEnrollment($enrollment))->values()->all();

        return $this->gpa->superseded($courses);
    }

    /**
     * A hold that keeps the student from seeing grades (decided 2026-10-22: off by default;
     * for unpaid fees or e.g. an academic integrity investigation).
     */
    public function blockingHold(StudentRecord $record): ?RegistrationHold
    {
        return RegistrationHold::query()->where('student_record_id', $record->id)->where('blocks_grades', true)->inForce()->first();
    }
}
