<?php

namespace App\Domain\Registration\Services;

use App\Domain\Registration\Enums\EnrollmentStatus;
use App\Models\Curriculum\CourseEquivalency;
use App\Models\Grading\GradeScale;
use App\Models\Grading\GradeScaleItem;
use App\Models\Person\StudentRecord;
use App\Models\Registration\Enrollment;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * What a student has taken, with equivalent courses counted (porting/porting-plan.md section
 * 10.4: substitutions valid at the time).
 */
class CourseHistory
{
    /** @var array<string, float|null> */
    private array $gradePoints = [];

    /**
     * The course and the courses that count for it on the date.
     *
     * @return list<int>
     */
    public function acceptedCourseIds(int $courseId, ?CarbonImmutable $on = null): array
    {
        $on ??= CarbonImmutable::today();
        $ids = [$courseId];

        foreach (CourseEquivalency::query()->effectiveOn($on)
            ->where(fn (Builder $query) => $query->where('course_id', $courseId)
                ->orWhere(fn (Builder $query) => $query->where('equivalent_course_id', $courseId)->where('is_mutual', true)))
            ->get() as $equivalency) {
            $ids[] = $equivalency->course_id === $courseId ? $equivalency->equivalent_course_id : $equivalency->course_id;
        }

        return array_values(array_unique($ids));
    }

    /**
     * Completed and passed enrollments of the person (any of their student records) in the
     * courses, at or above a minimum grade on the default scale.
     *
     * @param  list<int>  $courseIds
     */
    public function hasPassed(StudentRecord $record, array $courseIds, ?string $minimumGrade = null): bool
    {
        return Enrollment::query()
            ->whereIn('student_record_id', StudentRecord::query()->where('person_id', $record->person_id)->select('id'))
            ->whereIn('course_id', $courseIds)
            ->where('status', EnrollmentStatus::Completed)
            ->where('is_passed', true)
            ->get()
            ->contains(fn (Enrollment $enrollment) => $minimumGrade === null || $this->meetsMinimum($enrollment->grade_code, $minimumGrade));
    }

    /**
     * Earlier enrollments of this record in the courses that count as taking it (completed,
     * or withdrawn with a W), newest first.
     *
     * @param  list<int>  $courseIds
     * @return Collection<int, Enrollment>
     */
    public function earlierAttempts(StudentRecord $record, array $courseIds, int $exceptTermId): Collection
    {
        return Enrollment::query()->where('student_record_id', $record->id)->whereIn('course_id', $courseIds)
            ->where('term_id', '!=', $exceptTermId)
            ->where('status', EnrollmentStatus::Completed)
            ->orderByDesc('enrolled_at')->get();
    }

    public function meetsMinimum(?string $gradeCode, string $minimumGrade): bool
    {
        $points = $this->points($gradeCode);
        $minimum = $this->points($minimumGrade);

        // Without points on the scale (e.g. P/F) a pass is enough.
        return $points === null || $minimum === null || $points >= $minimum;
    }

    private function points(?string $gradeCode): ?float
    {
        if ($gradeCode === null) {
            return null;
        }

        if (! array_key_exists($gradeCode, $this->gradePoints)) {
            $scale = GradeScale::query()->where('is_default', true)->first();
            $item = $scale === null ? null : GradeScaleItem::query()->where('grade_scale_id', $scale->id)->where('code', $gradeCode)->first();
            $this->gradePoints[$gradeCode] = $item?->grade_points === null ? null : (float) $item->grade_points;
        }

        return $this->gradePoints[$gradeCode];
    }
}
