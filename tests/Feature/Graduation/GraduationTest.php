<?php

use App\Domain\Curriculum\Enums\ProgramRole;
use App\Domain\Curriculum\Enums\RequirementKind;
use App\Domain\Graduation\Actions\GraduateStudents;
use App\Domain\Graduation\Actions\RunDegreeAudits;
use App\Domain\Graduation\Actions\WaiveRequirement;
use App\Domain\Graduation\Enums\WaiverStatus;
use App\Domain\Graduation\Services\DegreeAudit;
use App\Domain\Graduation\Settings\GraduationSettings;
use App\Domain\Person\Enums\StudentStatus;
use App\Domain\Reporting\Services\ReportCatalog;
use App\Domain\Security\Actions\SyncPermissionCatalog;
use App\Domain\Security\Enums\MfaEnforcement;
use App\Domain\Security\Enums\Permission;
use App\Filament\Admin\Pages\ManageGraduationSettings;
use App\Filament\Admin\Pages\Reports;
use App\Filament\Admin\Resources\Graduation\Audits\DegreeAuditResource;
use App\Filament\Admin\Resources\Graduation\Audits\Pages\ListDegreeAudits;
use App\Filament\Admin\Resources\Graduation\Waivers\Pages\ListDegreeWaivers;
use App\Filament\Admin\Resources\StudentRecords\Pages\ViewStudentRecord;
use App\Livewire\Portal\MyGraduation;
use App\Models\Curriculum\Course;
use App\Models\Curriculum\CourseCategory;
use App\Models\Curriculum\CourseEquivalency;
use App\Models\Curriculum\CurriculumRequirement;
use App\Models\Curriculum\CurriculumRequirementSet;
use App\Models\Grading\GradeScale;
use App\Models\Graduation\DegreeAudit as StoredAudit;
use App\Models\Graduation\DegreeRequirementWaiver;
use App\Models\Offering\Section;
use App\Models\Person\StudentRecord;
use App\Models\Registration\Enrollment;
use App\Models\Security\Activity;
use App\Models\Term\Term;
use App\Models\User;
use App\Support\Database\DatabaseSessionContext;
use Carbon\CarbonImmutable;
use Database\Seeders\Essential\EssentialSeeder;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    setSecuritySettings(['mfa_enforcement' => MfaEnforcement::Disabled]);
    $this->seed(EssentialSeeder::class);
    app(SyncPermissionCatalog::class)->handle();
    Filament::setCurrentPanel('admin');
    GradeScale::factory()->korean()->create();

    $this->staff = User::factory()->withPermissions([Permission::AdminPanelAccess, Permission::GraduationAudit, Permission::GraduationProcess, Permission::ReportsView, Permission::StudentRecordsView, Permission::StudentStatusChangesApprove])->create();
    $this->record = StudentRecord::factory()->create(['catalog_year' => 2024, 'year_level' => 4]);
    $this->student = User::factory()->create(['person_id' => $this->record->person_id]);
    $this->term = Term::factory()->published()->between('2025-03-01', '2025-06-20')->create();

    // Categories: general (with a required subcategory), major.
    $this->general = CourseCategory::factory()->create(['code' => 'GEN']);
    $this->generalRequired = CourseCategory::factory()->create(['code' => 'GEN_REQ', 'parent_id' => $this->general->id]);
    $this->major = CourseCategory::factory()->create(['code' => 'MAJ']);
    $this->courses = collect(['GEN101' => $this->generalRequired, 'GEN102' => $this->general, 'MAJ201' => $this->major, 'MAJ202' => $this->major, 'MAJ203' => $this->major, 'OLD201' => $this->major])
        ->map(fn (CourseCategory $category, string $code) => Course::factory()->withVersion(['course_category_id' => $category->id, 'credits' => 3])->create(['code' => $code]));

    $this->set = CurriculumRequirementSet::query()->create([
        'program_id' => $this->record->program_id, 'program_role' => ProgramRole::Primary, 'catalog_year_from' => 2020, 'total_credits' => 12,
        'name' => ['en' => 'Requirements 2020', 'ko' => '2020 요건', 'zh_CN' => '2020 要求'],
    ]);
    $line = fn (RequirementKind $kind, array $attributes, array $courseIds = []) => tap(
        CurriculumRequirement::query()->create(['curriculum_requirement_set_id' => $this->set->id, 'kind' => $kind, ...$attributes]),
        fn (CurriculumRequirement $requirement) => $requirement->courses()->sync($courseIds),
    );
    $this->generalLine = $line(RequirementKind::CategoryCredits, ['course_category_id' => $this->general->id, 'min_credits' => 6, 'sort_order' => 1]);
    $this->requiredLine = $line(RequirementKind::RequiredCourses, ['sort_order' => 2], [$this->courses['MAJ201']->id]);
    $this->groupLine = $line(RequirementKind::CourseGroup, ['min_courses' => 2, 'sort_order' => 3], [$this->courses['MAJ202']->id, $this->courses['MAJ203']->id]);

    // Most cases have no term record (no GPA): the default 2.0 minimum is switched off unless a test sets it.
    $settings = app(GraduationSettings::class);
    $settings->minimum_cumulative_gpa = null;
    $settings->save();
    $this->audit = app(DegreeAudit::class);
});

function pass(StudentRecord $record, Term $term, Course $course, string $grade = 'A0'): Enrollment
{
    return Enrollment::factory()->inSection(Section::factory()->published()->inTerm($term)->forCourse($course)->create())->completed($grade)->create(['student_record_id' => $record->id]);
}

it('judges categories with subcategories, required courses with equivalents, course groups and totals', function () {
    $result = $this->audit->evaluate($this->record, $this->record->program, ProgramRole::Primary);
    expect($result->isSatisfied())->toBeFalse()->and($result->earnedCredits)->toBe(0.0)->and($result->requirements)->toHaveCount(3)
        ->and($result->issues)->toContain(__('graduation.issues.total_credits', ['earned' => 0.0, 'required' => 12.0]));

    pass($this->record, $this->term, $this->courses['GEN101']);
    pass($this->record, $this->term, $this->courses['GEN102']);
    // The old course counts for MAJ201 through an equivalency valid when it was taken.
    CourseEquivalency::query()->create(['course_id' => $this->courses['OLD201']->id, 'equivalent_course_id' => $this->courses['MAJ201']->id, 'is_mutual' => true, 'effective_start_date' => '2020-01-01']);
    pass($this->record, $this->term, $this->courses['OLD201']);
    pass($this->record, $this->term, $this->courses['MAJ202']);
    $failed = pass($this->record, $this->term, $this->courses['MAJ203'], 'F');

    $result = $this->audit->evaluate($this->record, $this->record->program, ProgramRole::Primary);
    [$general, $required, $group] = $result->requirements;
    expect($general->achievedCredits)->toBe(6.0)->and($general->isSatisfied)->toBeTrue()
        ->and($required->isSatisfied)->toBeTrue()->and($required->courses[0]['code'])->toBe('OLD201')
        ->and($group->achievedCourses)->toBe(1)->and($group->isSatisfied)->toBeFalse()
        ->and($result->earnedCredits)->toBe(12.0)->and($result->isSatisfied())->toBeFalse();

    // A waiver satisfies the group once another person approves it; the GPA minimum then blocks until grades are good enough.
    $waive = app(WaiveRequirement::class);
    $waiver = $waive->request($this->record, $this->groupLine, 'Transfer credit from abroad', $this->staff);
    expect(fn () => $waive->request($this->record, $this->groupLine, 'Again', $this->staff))->toThrow(ValidationException::class, __('graduation.errors.waiver_exists'));
    expect(fn () => $waive->approve($waiver, $this->staff))->toThrow(ValidationException::class, __('graduation.errors.own_waiver'));
    expect($this->audit->evaluate($this->record, $this->record->program, ProgramRole::Primary)->requirements[2]->isWaived)->toBeFalse();
    $approver = User::factory()->withPermissions([Permission::AdminPanelAccess, Permission::GraduationProcess, Permission::StudentRecordsView])->create();
    expect(fn () => $waive->lift($waiver, $approver, 'x'))->toThrow(ValidationException::class, __('graduation.errors.waiver_not_approved'));
    $waive->approve($waiver, $approver, 'Checked');
    expect($waiver->refresh()->status)->toBe(WaiverStatus::Approved);
    $settings = app(GraduationSettings::class);
    $settings->minimum_cumulative_gpa = null;
    $settings->save();
    expect($this->audit->evaluate($this->record, $this->record->program, ProgramRole::Primary)->isSatisfied())->toBeTrue();

    $settings->minimum_cumulative_gpa = 3.0;
    $settings->save();
    $result = $this->audit->evaluate($this->record, $this->record->program, ProgramRole::Primary);
    expect($result->issues)->toHaveCount(1)->and($result->issues[0])->toContain('3');

    // Lifting takes the waiver out of the audit again; the admin screens decide and lift.
    $waive->lift($waiver, $approver, 'Transcript turned out incomplete');
    expect($waiver->refresh()->status)->toBe(WaiverStatus::Lifted)
        ->and($this->audit->evaluate($this->record, $this->record->program, ProgramRole::Primary)->requirements[2]->isSatisfied)->toBeFalse();
    $second = $waive->request($this->record, $this->groupLine, 'Second look', $this->staff);
    Livewire::actingAs($approver)->test(ListDegreeWaivers::class)->assertCanSeeTableRecords([$second])->callTableAction('approve', $second, ['note' => 'OK'])->assertHasNoTableActionErrors();
    expect($second->refresh()->status)->toBe(WaiverStatus::Approved)->and(Activity::query()->where('event', 'graduation.waiver_approved')->count())->toBe(2);
    Livewire::actingAs($this->staff)->test(ListDegreeWaivers::class)->filterTable('status', WaiverStatus::Approved->value)->assertTableActionHidden('approve', $second)
        ->callTableAction('lift', $second, ['reason' => 'Error'])->assertHasNoTableActionErrors();
    Livewire::actingAs($this->staff)->test(ViewStudentRecord::class, ['record' => $this->record->getRouteKey()])
        ->callAction('waiveRequirement', ['curriculum_requirement_id' => $this->groupLine->id, 'reason' => 'From the screen'])->assertHasNoActionErrors();
    expect(DegreeRequirementWaiver::query()->where('status', WaiverStatus::Pending)->count())->toBe(1);

    $other = StudentRecord::factory()->create(['catalog_year' => 2010]);
    expect($this->audit->evaluate($other, $other->program, ProgramRole::Primary)->issues[0])->toBe(__('graduation.issues.no_requirement_set', ['year' => 2010]));
});

it('stores audits, graduates from the latest satisfied one and issues a degree number', function () {
    $graduate = app(GraduateStudents::class);
    expect(fn () => $graduate->handle($this->record, CarbonImmutable::parse('2026-02-20'), $this->staff, 'Spring commencement'))->toThrow(ValidationException::class, __('graduation.errors.no_audit'));

    $this->artisan('degree-audit:run', ['--student-number' => $this->record->student_number, '--actor' => $this->staff->email])->expectsOutputToContain('1 student(s) audited, 0 satisfied.')->assertSuccessful();
    expect(StoredAudit::query()->count())->toBe(1);
    expect(fn () => $graduate->handle($this->record, CarbonImmutable::parse('2026-02-20'), $this->staff, 'Spring commencement'))->toThrow(ValidationException::class, __('graduation.errors.audit_not_satisfied'));

    foreach (['GEN101', 'GEN102', 'MAJ201', 'MAJ202', 'MAJ203'] as $code) {
        pass($this->record, $this->term, $this->courses[$code]);
    }
    $this->artisan('degree-audit:run', ['--term' => $this->term->code, '--min-year-level' => 4, '--actor' => $this->staff->email])->expectsOutputToContain('1 student(s) audited, 1 satisfied.')->assertSuccessful();
    $this->artisan('degree-audit:run', ['--term' => 'NOPE'])->assertFailed();
    expect(StoredAudit::query()->newestPerStudent()->get())->toHaveCount(1)->and(StoredAudit::query()->count())->toBe(2);

    $clerk = User::factory()->withPermissions([Permission::GraduationAudit, Permission::StudentRecordsView])->create();
    expect(fn () => $graduate->handle($this->record, CarbonImmutable::parse('2026-02-20'), $clerk, 'x'))->toThrow(AuthorizationException::class);

    $graduate->handle($this->record, CarbonImmutable::parse('2026-02-20'), $this->staff, 'Spring commencement');
    expect($this->record->refresh())->status->toBe(StudentStatus::Graduated)->degree_number->toBe($this->record->program->code.'-2026-00001')
        ->and($this->record->graduation_date->toDateString())->toBe('2026-02-20')
        ->and(Activity::query()->where('event', 'graduation.processed')->exists())->toBeTrue();
    expect(fn () => $graduate->handle($this->record, CarbonImmutable::parse('2026-02-20'), $this->staff, 'Again'))->toThrow(ValidationException::class, __('graduation.errors.not_enrolled'));

    $second = StudentRecord::factory()->create(['program_id' => $this->record->program_id, 'catalog_year' => 2024]);
    foreach (['GEN101', 'GEN102', 'MAJ201', 'MAJ202', 'MAJ203'] as $code) {
        pass($second, $this->term, $this->courses[$code]);
    }
    app(RunDegreeAudits::class)->forRecord($second, $this->staff);
    $summary = $graduate->batch([$second, StudentRecord::factory()->create()], CarbonImmutable::parse('2026-02-20'), $this->staff, 'Batch');
    expect($summary['graduated'])->toBe(1)->and($summary['errors'])->toHaveCount(1)->and($second->refresh()->degree_number)->toBe($this->record->program->code.'-2026-00002');
});

it('shows audits to staff and the student at the database level and in the screens', function () {
    pass($this->record, $this->term, $this->courses['GEN101']);
    app(RunDegreeAudits::class)->forRecord($this->record, $this->staff);
    $stranger = User::factory()->create(['person_id' => StudentRecord::factory()->create()->person_id]);
    $context = app(DatabaseSessionContext::class);

    $context->forUser($this->student->id);
    expect(StoredAudit::query()->count())->toBe(1)->and(StoredAudit::query()->update(['is_satisfied' => true]))->toBe(0);
    $context->forUser($stranger->id);
    expect(StoredAudit::query()->count())->toBe(0);
    $context->forUser($this->staff->id);
    expect(StoredAudit::query()->count())->toBe(1);

    $this->actingAs($this->staff)->get(DegreeAuditResource::getUrl('index'))->assertOk()->assertSee($this->record->student_number);
    $this->actingAs($this->staff)->get(ManageGraduationSettings::getUrl())->assertOk();
    Livewire::actingAs($this->staff)->test(ListDegreeAudits::class)->callTableAction('rerun', StoredAudit::query()->first())->assertHasNoTableActionErrors();
    expect(StoredAudit::query()->count())->toBe(2);
    Livewire::actingAs($this->staff)->test(ViewStudentRecord::class, ['record' => $this->record->getRouteKey()])->callAction('degreeAudit')->assertHasNoActionErrors();
    expect(StoredAudit::query()->count())->toBe(3);
    Livewire::actingAs($this->student)->test(MyGraduation::class)->assertSee(__('graduation.portal.title'))->assertSee('GEN101');
});

it('serves the standard reports from the reporting views and as CSV', function () {
    pass($this->record, $this->term, $this->courses['GEN101']);
    app(RunDegreeAudits::class)->forRecord($this->record, $this->staff);
    $catalog = app(ReportCatalog::class);

    expect($catalog->rows('students', ['status' => 'enrolled'])->pluck('student_number'))->toContain($this->record->student_number)
        ->and($catalog->rows('headcount', [])->sum('students'))->toBeGreaterThan(0)
        ->and($catalog->rows('section_enrollments', ['term' => $this->term->id])->first()->enrolled)->toBe(1)
        ->and($catalog->rows('not_registered', ['term' => $this->term->id])->pluck('student_number'))->not->toContain($this->record->student_number)
        ->and($catalog->rows('graduation_candidates', ['satisfied' => 'no'])->pluck('student_number'))->toContain($this->record->student_number)
        ->and($catalog->rows('status_changes', ['date_from' => '2000-01-01']))->toHaveCount(0);

    $csv = $this->actingAs($this->staff)->get(route('reports.csv', ['report' => 'students', 'status' => 'enrolled']))->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    expect($csv->getContent())->toContain($this->record->student_number)->and(Activity::query()->where('event', 'reports.exported')->exists())->toBeTrue();
    $this->actingAs($this->staff)->get(route('reports.csv', ['report' => 'nope']))->assertNotFound();
    $this->actingAs($this->student)->get(route('reports.csv', ['report' => 'students']))->assertForbidden();

    $this->actingAs($this->staff)->get(Reports::getUrl())->assertOk();
    Livewire::actingAs($this->staff)->test(Reports::class)->fillForm(['report' => 'section_enrollments', 'term' => $this->term->id])->assertSee('GEN101');

    // The views apply the reader's row level security: the student sees only themselves.
    app(DatabaseSessionContext::class)->forUser($this->student->id);
    expect($catalog->rows('students', [])->pluck('student_number')->all())->toBe([$this->record->student_number]);
});
