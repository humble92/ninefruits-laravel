<?php

namespace App\Domain\Graduation\Services;

use App\Domain\Assessment\Services\AssessmentPassCheck;
use App\Domain\Curriculum\Enums\ProgramRole;
use App\Domain\Curriculum\Enums\RequirementKind;
use App\Domain\Graduation\Data\DegreeAuditResult;
use App\Domain\Graduation\Data\RequirementResult;
use App\Domain\Graduation\Settings\GraduationSettings;
use App\Domain\Person\Enums\AdditionalProgramRole;
use App\Domain\Registration\Enums\EnrollmentStatus;
use App\Domain\Registration\Services\CourseHistory;
use App\Models\Curriculum\CourseCategory;
use App\Models\Curriculum\CurriculumRequirement;
use App\Models\Curriculum\CurriculumRequirementSet;
use App\Models\Grading\TermRecord;
use App\Models\Graduation\DegreeRequirementWaiver;
use App\Models\Organization\Program;
use App\Models\Person\StudentRecord;
use App\Models\Person\StudentRecordProgram;
use App\Models\Registration\Enrollment;
use Illuminate\Support\Collection;

/**
 * Judges a student against the requirement set of their catalog year (legacy
 * report_chkgrad only printed the credits; the decision was a signature). Passed
 * enrollments of every record of the person count, equivalent courses are accepted as of
 * the term they were taken, and waived requirements count as satisfied.
 */
class DegreeAudit
{
    public function __construct(
        private readonly CourseHistory $history,
        private readonly AssessmentPassCheck $assessments,
        private readonly GraduationSettings $settings,
    ) {}

    /**
     * The primary program first, then the student's active second majors and minors.
     *
     * @return list<DegreeAuditResult>
     */
    public function evaluateAll(StudentRecord $record): array
    {
        $record->loadMissing('program');
        $results = [$this->evaluate($record, $record->program, ProgramRole::Primary)];

        foreach (StudentRecordProgram::query()->where('student_record_id', $record->id)->whereNull('end_date')->with('program')->get() as $additional) {
            $results[] = $this->evaluate($record, $additional->program, $additional->role === AdditionalProgramRole::Minor ? ProgramRole::Minor : ProgramRole::SecondMajor);
        }

        return $results;
    }

    public function evaluate(StudentRecord $record, Program $program, ProgramRole $role): DegreeAuditResult
    {
        $set = CurriculumRequirementSet::query()->where('program_id', $program->id)->where('program_role', $role)
            ->forCatalogYear($record->catalog_year)->with(['requirements.courses', 'requirements.assessmentType'])->first();
        $passed = $this->passedEnrollments($record);
        $earned = round((float) $passed->sum(fn (Enrollment $enrollment) => (float) $enrollment->credits), 1);
        $gpa = TermRecord::query()->where('student_record_id', $record->id)->orderByDesc('term_id')->value('cumulative_gpa');
        $gpa = $gpa === null ? null : (float) $gpa;
        $minimumGpa = $role === ProgramRole::Primary ? $this->settings->minimum_cumulative_gpa : null;
        $issues = [];
        $requirements = [];

        if ($set === null) {
            $issues[] = __('graduation.issues.no_requirement_set', ['year' => $record->catalog_year]);
        } else {
            $waived = DegreeRequirementWaiver::query()->where('student_record_id', $record->id)->pluck('curriculum_requirement_id')->flip();

            foreach ($set->requirements as $requirement) {
                $result = $this->judge($record, $requirement, $passed, $waived->has($requirement->id));
                $requirements[] = $result;

                if (! $result->isSatisfied) {
                    $issues[] = __('graduation.issues.requirement', ['name' => $result->name]);
                }
            }

            if ($set->total_credits !== null && $earned < (float) $set->total_credits) {
                $issues[] = __('graduation.issues.total_credits', ['earned' => $earned, 'required' => (float) $set->total_credits]);
            }
        }

        if ($minimumGpa !== null && ($gpa === null || $gpa < $minimumGpa)) {
            $issues[] = __('graduation.issues.gpa', ['gpa' => $gpa ?? '-', 'minimum' => $minimumGpa]);
        }

        return new DegreeAuditResult($program, $role, $set, $earned, $set?->total_credits === null ? null : (float) $set->total_credits, $gpa, $minimumGpa, $requirements, $issues);
    }

    /**
     * @param  Collection<int, Enrollment>  $passed
     */
    private function judge(StudentRecord $record, CurriculumRequirement $requirement, Collection $passed, bool $waived): RequirementResult
    {
        $name = $requirement->name ?? $requirement->kind->label();
        $required = $requirement->min_credits === null ? null : (float) $requirement->min_credits;

        switch ($requirement->kind) {
            case RequirementKind::CategoryCredits:
                $categoryIds = $this->categoryAndDescendants((int) $requirement->course_category_id);
                $counted = $passed->filter(fn (Enrollment $enrollment) => in_array($enrollment->course_category_id, $categoryIds, true));
                $credits = round((float) $counted->sum(fn (Enrollment $e) => (float) $e->credits), 1);

                return new RequirementResult($requirement->id, $requirement->kind, $name, $required, null, $credits, $counted->count(), $waived || $credits >= (float) $required, $waived, $this->describe($counted));

            case RequirementKind::RequiredCourses:
                $counted = collect();
                $missing = [];

                foreach ($requirement->courses as $course) {
                    $match = $this->firstPassed($passed, $course->id);
                    $match === null ? $missing[] = $course->code : $counted->push($match);
                }

                $credits = round((float) $counted->sum(fn (Enrollment $e) => (float) $e->credits), 1);

                return new RequirementResult($requirement->id, $requirement->kind, $name, null, $requirement->courses->count(), $credits, $counted->count(), $waived || $missing === [], $waived, $this->describe($counted), $missing);

            case RequirementKind::CourseGroup:
                $counted = collect();

                foreach ($requirement->courses as $course) {
                    if (($match = $this->firstPassed($passed, $course->id)) !== null) {
                        $counted->push($match);
                    }
                }

                $credits = round((float) $counted->sum(fn (Enrollment $e) => (float) $e->credits), 1);
                $satisfied = ($required === null || $credits >= $required) && ($requirement->min_courses === null || $counted->count() >= $requirement->min_courses);

                return new RequirementResult($requirement->id, $requirement->kind, $name, $required, $requirement->min_courses, $credits, $counted->count(), $waived || $satisfied, $waived, $this->describe($counted));

            case RequirementKind::AssessmentPass:
                $type = $requirement->assessmentType;
                $passedExam = $type !== null && $this->assessments->hasPassed($record, $type);

                return new RequirementResult($requirement->id, $requirement->kind, $type === null ? $name : $type->name, null, null, 0, $passedExam ? 1 : 0, $waived || $passedExam, $waived);
        }
    }

    /**
     * Passed enrollments of the person, across their student records, with the course and version.
     *
     * @return Collection<int, Enrollment>
     */
    private function passedEnrollments(StudentRecord $record): Collection
    {
        return Enrollment::query()
            ->whereIn('student_record_id', StudentRecord::query()->where('person_id', $record->person_id)->select('id'))
            ->where('status', EnrollmentStatus::Completed)->where('is_passed', true)
            ->with(['course', 'version', 'term'])->get();
    }

    /**
     * The passed enrollment in the course or in a course accepted as equivalent when it was taken.
     *
     * @param  Collection<int, Enrollment>  $passed
     */
    private function firstPassed(Collection $passed, int $courseId): ?Enrollment
    {
        return $passed->first(fn (Enrollment $enrollment) => $enrollment->course_id === $courseId
            || in_array($courseId, $this->history->acceptedCourseIds($enrollment->course_id, $enrollment->term->start_date), true));
    }

    /**
     * @return list<int>
     */
    private function categoryAndDescendants(int $categoryId): array
    {
        $byParent = CourseCategory::query()->get(['id', 'parent_id'])->groupBy('parent_id');
        $ids = [$categoryId];

        for ($i = 0; $i < count($ids); $i++) {
            foreach ($byParent->get($ids[$i], collect()) as $child) {
                $ids[] = $child->id;
            }
        }

        return $ids;
    }

    /**
     * @param  Collection<int, Enrollment>  $enrollments
     * @return list<array{code: string, title: string, credits: string, grade: string|null}>
     */
    private function describe(Collection $enrollments): array
    {
        return $enrollments->sortBy(fn (Enrollment $e) => $e->course->code)->values()
            ->map(fn (Enrollment $e) => ['code' => $e->course->code, 'title' => $e->version->title, 'credits' => (string) $e->credits, 'grade' => $e->grade_code])->all();
    }
}
