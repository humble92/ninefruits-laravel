<?php

namespace Database\Seeders\Demo;

use App\Domain\Assessment\Actions\RecordAssessmentResult;
use App\Domain\Assessment\Services\AssessmentPassCheck;
use App\Domain\Curriculum\Enums\ProgramRole;
use App\Domain\Curriculum\Enums\RequirementKind;
use App\Domain\Graduation\Actions\GraduateStudents;
use App\Domain\Graduation\Actions\RunDegreeAudits;
use App\Domain\Graduation\Actions\WaiveRequirement;
use App\Domain\Graduation\Services\DegreeAudit as DegreeAuditService;
use App\Domain\Person\Enums\StudentStatus;
use App\Domain\Term\Enums\TermStatus;
use App\Models\Assessment\AssessmentRegistration;
use App\Models\Assessment\AssessmentSession;
use App\Models\Assessment\AssessmentType;
use App\Models\Curriculum\Course;
use App\Models\Curriculum\CourseVersion;
use App\Models\Curriculum\CurriculumRequirement;
use App\Models\Curriculum\CurriculumRequirementSet;
use App\Models\Grading\TermRecord;
use App\Models\Graduation\DegreeAudit;
use App\Models\Offering\Section;
use App\Models\Person\StudentRecord;
use App\Models\Registration\Enrollment;
use App\Models\Term\Term;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Degree audits for the current term's third- and fourth-year students. Three fourth-year
 * students get a complete course history (every requirement of their curriculum, 130
 * credits) so that satisfied audits exist; one of them is graduated with a degree number.
 */
class DemoGraduationSeeder extends Seeder
{
    /** @var array<int, Section> */
    private array $sections = [];

    public function __construct(private readonly RecordAssessmentResult $results) {}

    public function run(RunDegreeAudits $audits, GraduateStudents $graduate, DegreeAuditService $audit, WaiveRequirement $waive): void
    {
        $registrar = User::query()->where('email', 'registrar@'.config('setup.demo.email_domain'))->first();
        $approver = User::query()->where('email', 'system-administrator@'.config('setup.demo.email_domain'))->first();
        $current = Term::query()->where('status', TermStatus::Published)->where('start_date', '<=', now()->toDateString())->orderByDesc('start_date')->first();
        $past = Term::query()->where('status', TermStatus::GradesClosed)->orderBy('start_date')->first();

        if ($registrar === null || $approver === null || $current === null || $past === null || DegreeAudit::query()->exists()) {
            return;
        }

        $seniors = StudentRecord::query()->where('status', StudentStatus::Enrolled)->where('year_level', 4)
            ->whereIn('id', TermRecord::query()->where('cumulative_gpa', '>=', 2.5)->select('student_record_id'))
            ->with('program')->orderBy('student_number')->limit(3)->get();

        foreach ($seniors as $record) {
            $this->completeRequirements($record, $past);
            $this->passExaminations($record, $registrar, $audit);
            $this->waiveWhatRemains($record, $registrar, $approver, $audit, $waive);
        }

        $audits->forAll($current, $registrar, minimumYearLevel: 3);

        // Graduate the first senior whose stored audit is satisfied (a tiny catalog may not reach the total credits).
        $candidate = $seniors->first(fn (StudentRecord $record) => DegreeAudit::query()->where('student_record_id', $record->id)
            ->where('program_role', ProgramRole::Primary)->orderByDesc('id')->value('is_satisfied') === true);

        if ($candidate !== null) {
            $graduate->handle($candidate, $current->start_date->subDays(10), $registrar, 'Demo graduation');
        }
    }

    /**
     * Passed enrollments for every requirement of the primary set, then filler courses up
     * to the total credits.
     */
    private function completeRequirements(StudentRecord $record, Term $term): void
    {
        $set = CurriculumRequirementSet::query()->where('program_id', $record->program_id)->where('program_role', ProgramRole::Primary)
            ->forCatalogYear($record->catalog_year)->with('requirements.courses')->first();

        if ($set === null) {
            return;
        }

        $passed = Enrollment::query()->where('student_record_id', $record->id)->where('is_passed', true)->pluck('course_id')->flip()->all();
        $credits = (float) Enrollment::query()->where('student_record_id', $record->id)->where('is_passed', true)->sum('credits');

        foreach ($set->requirements as $requirement) {
            /** @var CurriculumRequirement $requirement */
            $courses = match ($requirement->kind) {
                RequirementKind::CategoryCredits => $this->coursesInCategory((int) $requirement->course_category_id, (float) $requirement->min_credits),
                RequirementKind::RequiredCourses => $requirement->courses,
                RequirementKind::CourseGroup => $requirement->courses->take(max((int) ceil((float) $requirement->min_credits / 3), (int) $requirement->min_courses)),
                RequirementKind::AssessmentPass => collect(),
            };

            foreach ($courses as $course) {
                if (! isset($passed[$course->id])) {
                    $credits += $this->pass($record, $course, $term);
                    $passed[$course->id] = true;
                }
            }
        }

        // Filler up to the total: the unit's courses first, then the rest of the catalog.
        foreach (Course::query()->orderByRaw('(org_unit_id = ?) desc', [$record->org_unit_id])->orderBy('code')->get() as $course) {
            if ($credits >= (float) ($set->total_credits ?? 0)) {
                break;
            }

            if (! isset($passed[$course->id])) {
                $credits += $this->pass($record, $course, $term);
                $passed[$course->id] = true;
            }
        }
    }

    /**
     * @return Collection<int, Course>
     */
    private function coursesInCategory(int $categoryId, float $credits): Collection
    {
        $picked = collect();
        $sum = 0.0;

        foreach (CourseVersion::query()->where('course_category_id', $categoryId)->with('course')->orderBy('course_id')->get() as $version) {
            if ($sum >= $credits) {
                break;
            }

            $picked->push($version->course);
            $sum += (float) $version->credits;
        }

        return $picked;
    }

    /**
     * A passed English examination (the curriculum requires it): a past session with a result.
     */
    private function passExaminations(StudentRecord $record, User $registrar, DegreeAuditService $audit): void
    {
        $type = AssessmentType::query()->where('code', 'ENG_COMP')->with('components')->first();

        if ($type === null || app(AssessmentPassCheck::class)->hasPassed($record, $type)) {
            return;
        }

        $session = AssessmentSession::query()->firstOrCreate(['assessment_type_id' => $type->id, 'held_date' => CarbonImmutable::today()->subMonths(9)], ['venue' => 'Main Hall 101']);
        $registration = AssessmentRegistration::query()->firstOrCreate(['assessment_session_id' => $session->id, 'student_record_id' => $record->id], ['registered_at' => $session->held_date->subWeeks(2), 'registered_by_id' => $registrar->id]);
        $this->results->forRegistration($registration, $type->components->mapWithKeys(fn ($c) => [$c->code => 20])->all(), null, $registrar, applyingCorrection: $session->results_published_at !== null);
    }

    /**
     * Whatever the small demo catalog cannot satisfy (a category with too few courses) is
     * waived with a reason, which also shows the waiver in the screens.
     */
    private function waiveWhatRemains(StudentRecord $record, User $registrar, User $approver, DegreeAuditService $audit, WaiveRequirement $waive): void
    {
        $record->loadMissing('program');

        foreach ($audit->evaluate($record, $record->program, ProgramRole::Primary)->requirements as $result) {
            if (! $result->isSatisfied) {
                $waiver = $waive->request($record, CurriculumRequirement::query()->findOrFail($result->requirementId), 'Demo: transfer credit recognized', $registrar);
                $waive->approve($waiver, $approver, 'Checked the transcript');
            }
        }
    }

    private function pass(StudentRecord $record, Course $course, Term $term): float
    {
        // A section of its own per course: a student may already sit (and have failed) in the term's regular section.
        $section = $this->sections[$course->id] ??= Section::factory()->published()->inTerm($term)->forCourse($course)->create(['capacity' => 200, 'section_code' => 'GR']);
        $enrollment = Enrollment::factory()->inSection($section)->completed('A0')->create(['student_record_id' => $record->id, 'enrolled_at' => CarbonImmutable::instance($term->start_date)]);

        return (float) $enrollment->credits;
    }
}
