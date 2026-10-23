<?php

use App\Domain\Grading\Actions\ManageEvaluationMethodRequests;
use App\Domain\Grading\Actions\SaveGrades;
use App\Domain\Grading\Actions\SaveGradeScale;
use App\Domain\Grading\Enums\EvaluationMethod;
use App\Domain\Grading\Enums\GradeStatus;
use App\Domain\Grading\Enums\RequestStatus;
use App\Domain\Grading\Notifications\EvaluationMethodDecidedNotification;
use App\Domain\Grading\Notifications\GradeExceptionNotification;
use App\Domain\Grading\Services\GradeDistributionCheck;
use App\Domain\Grading\Settings\GradingSettings;
use App\Domain\Security\Enums\Permission;
use App\Domain\Security\Enums\ScopeType;
use App\Domain\Term\Enums\TermWindowType;
use App\Livewire\Portal\GradeEntry;
use App\Models\Grading\EvaluationMethodRequest;
use App\Models\Grading\GradeScale;
use App\Models\Offering\Section;
use App\Models\Offering\SectionInstructor;
use App\Models\Person\Instructor;
use App\Models\Registration\Enrollment;
use App\Models\Security\Activity;
use App\Models\Term\Term;
use App\Models\Term\TermWindow;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    Notification::fake();
    GradeScale::factory()->korean()->create();
    $settings = app(GradingSettings::class);
    $settings->relative_grade_caps = ['A' => 30, 'B' => 40];
    $settings->relative_cap_tolerance_percent = 1;
    $settings->relative_minimum_students = 10;
    $settings->save();

    $this->term = Term::factory()->published()->between(now()->subMonths(4)->toDateString(), now()->addWeek()->toDateString())->create();
    TermWindow::factory()->for($this->term)->ofType(TermWindowType::GradeEntry)->between(now()->subDay(), now()->addWeek())
        ->create(['term_session_id' => $this->term->fullTermSession()->id]);
    $this->section = Section::factory()->published()->inTerm($this->term)->create(['capacity' => 20, 'evaluation_method' => EvaluationMethod::Relative]);
    $instructor = Instructor::factory()->create();
    SectionInstructor::query()->create(['section_id' => $this->section->id, 'instructor_id' => $instructor->id, 'role' => 'primary']);
    $this->teacher = User::factory()->create(['person_id' => $instructor->person_id]);
    $this->registrar = User::factory()->withPermissions([Permission::GradesManage, Permission::GradeExceptionsReview])->create();
    $this->reviewer = User::factory()->withPermissions([Permission::GradeExceptionsReview], ScopeType::OrgUnit, $this->section->org_unit_id)->create();
    $this->enrollments = Enrollment::factory()->count(10)->inSection($this->section)->create();
    $this->section->forceFill(['enrolled_count' => 10])->save();
});

/**
 * @param  list<string>  $codes  one per student, in order
 */
function gradesOf(object $test, array $codes): array
{
    return collect($test->enrollments)->values()->mapWithKeys(fn (Enrollment $enrollment, int $index) => [$enrollment->id => ['grade_code' => $codes[$index]]])->all();
}

it('checks grade groups against the caps with tolerance, and exempts small sections', function () {
    $check = app(GradeDistributionCheck::class);

    // 4 of 10 A (40%) is over the 30% + 1 cap; 3 of 10 (30%) is not; 31% would still pass.
    expect($check->check(10, ['A' => 4, 'B' => 4, 'C' => 2]))->passes()->toBeFalse()
        ->and($check->check(10, ['A' => 3, 'B' => 4, 'C' => 3]))->passes()->toBeTrue()
        ->and($check->check(100, ['A' => 31, 'B' => 40, 'C' => 29]))->passes()->toBeTrue()
        ->and($check->check(100, ['A' => 32, 'B' => 40, 'C' => 28])->violations)->toHaveCount(1)
        // Under the minimum, or absolute evaluation: not checked.
        ->and($check->check(9, ['A' => 9]))->checked->toBeFalse()->passes()->toBeTrue()
        ->and($check->check(50, ['A' => 50], relative: false)->checked)->toBeFalse();
});

it('keeps instructors from submitting relative grades outside the caps', function () {
    $save = app(SaveGrades::class);
    $save->save($this->section, gradesOf($this, ['A+', 'A0', 'A+', 'A0', 'B+', 'B0', 'B+', 'C+', 'C0', 'D0']), $this->teacher);

    expect(fn () => $save->submit($this->section, $this->teacher))->toThrow(ValidationException::class)
        ->and($this->enrollments[0]->refresh()->grade_status)->toBe(GradeStatus::Draft);

    // 3 A, 4 B of 10: within the caps.
    $save->save($this->section, gradesOf($this, ['A+', 'A0', 'A+', 'B+', 'B+', 'B0', 'B+', 'C+', 'C0', 'D0']), $this->teacher);
    expect($save->submit($this->section, $this->teacher))->toBe(10)
        ->and($this->section->refresh()->grade_exception_at)->toBeNull();
});

it('lets staff submit outside the caps only as a recorded exception with a reason, told to the reviewers', function () {
    $save = app(SaveGrades::class);
    $save->save($this->section, gradesOf($this, ['A+', 'A0', 'A+', 'A0', 'B+', 'B0', 'B+', 'C+', 'C0', 'D0']), $this->registrar);

    expect(fn () => $save->submit($this->section, $this->registrar))->toThrow(ValidationException::class, __('grading.errors.exception_reason_required'));

    $save->submit($this->section, $this->registrar, 'Honors seminar: the department approved the distribution');

    expect($this->section->refresh())->grade_exception_reason->toContain('Honors seminar')->grade_exception_by_id->toBe($this->registrar->id)
        ->and($this->enrollments[0]->refresh()->grade_status)->toBe(GradeStatus::Submitted)
        ->and(Activity::query()->where('event', 'grades.submitted_with_exception')->count())->toBe(1);
    Notification::assertSentTo($this->reviewer, GradeExceptionNotification::class);
    Notification::assertNotSentTo($this->registrar, GradeExceptionNotification::class);

    Livewire::actingAs($this->registrar)->test(GradeEntry::class, ['section' => $this->section])->assertSee(__('grading.entry.exception_title'));
});

it('changes the evaluation method only through a request the scheduling staff decide', function () {
    $section = Section::factory()->published()->inTerm($this->term)->create();
    SectionInstructor::query()->create(['section_id' => $section->id, 'instructor_id' => Instructor::query()->sole()->id, 'role' => 'primary']);
    $enrollment = Enrollment::factory()->inSection($section)->create();
    $scheduler = User::factory()->withPermissions([Permission::SectionsManage], ScopeType::OrgUnit, $section->org_unit_id)->create();
    $requests = app(ManageEvaluationMethodRequests::class);

    expect($section->evaluation_method)->toBe(EvaluationMethod::Absolute)
        ->and(fn () => $requests->request($section, EvaluationMethod::Relative, 'Seminar', User::factory()->create()))->toThrow(AuthorizationException::class)
        ->and(fn () => $requests->request($section, EvaluationMethod::Absolute, 'Same', $this->teacher))->toThrow(ValidationException::class);

    app(SaveGrades::class)->save($section, [$enrollment->id => ['score' => '88']], $this->teacher);
    $request = $requests->request($section, EvaluationMethod::Relative, 'Discussion seminar graded on participation', $this->teacher);

    expect(fn () => $requests->request($section, EvaluationMethod::Relative, 'Again', $this->teacher))->toThrow(ValidationException::class)
        ->and(fn () => $requests->approve($request, User::factory()->withPermissions([Permission::GradesManage])->create()))->toThrow(AuthorizationException::class);

    // Approval switches the method and clears the draft entered under the old one.
    expect($requests->approve($request, $scheduler, 'Agreed'))->toBe(1)
        ->and($section->refresh()->evaluation_method)->toBe(EvaluationMethod::Relative)
        ->and($enrollment->refresh())->grade_status->toBe(GradeStatus::None)->score->toBeNull()
        ->and($request->refresh()->status)->toBe(RequestStatus::Approved);
    Notification::assertSentTo($this->teacher, EvaluationMethodDecidedNotification::class);

    // Not once grades are submitted.
    app(SaveGrades::class)->save($section, [$enrollment->id => ['grade_code' => 'B0']], $this->teacher);
    app(SaveGrades::class)->submit($section, $this->teacher);
    expect(fn () => $requests->request($section, EvaluationMethod::Absolute, 'Back', $this->teacher))->toThrow(ValidationException::class, __('grading.errors.method_has_grades'));
});

it('lets the instructor request and check from the grade entry screen', function () {
    $section = Section::factory()->published()->inTerm($this->term)->create();
    SectionInstructor::query()->create(['section_id' => $section->id, 'instructor_id' => Instructor::query()->sole()->id, 'role' => 'primary']);

    Livewire::actingAs($this->teacher)->test(GradeEntry::class, ['section' => $section])
        ->set('methodReason', 'Seminar')
        ->call('requestMethod')
        ->assertSet('messageTone', 'success')
        ->assertSee(__('grading.entry.method_pending', ['method' => EvaluationMethod::Relative->label()]));

    expect(EvaluationMethodRequest::query()->where('section_id', $section->id)->pending()->exists())->toBeTrue();

    $codes = ['A+', 'A0', 'A+', 'A0', 'B+', 'B0', 'B+', 'C+', 'C0', 'D0'];
    $page = Livewire::actingAs($this->teacher)->test(GradeEntry::class, ['section' => $this->section]);

    foreach ($this->enrollments as $index => $enrollment) {
        $page->set("grades.{$enrollment->id}.grade_code", $codes[$index]);
    }

    $page->call('validateDistribution')->assertSet('messageTone', 'error')->assertSee('40');
});

it('fixes the grade scale: no second default, no clearing the default', function () {
    $other = GradeScale::factory()->create();

    expect(fn () => app(SaveGradeScale::class)->handle($other, ['is_default' => true]))->toThrow(ValidationException::class, __('grading.errors.scale_fixed'))
        ->and(fn () => app(SaveGradeScale::class)->handle(GradeScale::query()->where('is_default', true)->sole(), ['is_default' => false]))->toThrow(ValidationException::class)
        ->and(app(SaveGradeScale::class)->handle($other, ['name' => ['en' => 'Renamed']])->is_default)->toBeFalse();
});
