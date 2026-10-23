<?php

use App\Domain\Curriculum\Enums\GradingBasis;
use App\Domain\Grading\Actions\FinalizeGrades;
use App\Domain\Grading\Actions\ManageGradeChanges;
use App\Domain\Grading\Actions\SaveGrades;
use App\Domain\Grading\Enums\EvaluationMethod;
use App\Domain\Grading\Enums\GradeChangeStatus;
use App\Domain\Grading\Enums\GradeStatus;
use App\Domain\Grading\Events\GradesFinalized;
use App\Domain\Grading\Notifications\GradesReturnedNotification;
use App\Domain\Grading\Settings\GradingSettings;
use App\Domain\Offering\Actions\UpdateSection;
use App\Domain\Registration\Enums\EnrollmentStatus;
use App\Domain\Security\Enums\Permission;
use App\Domain\Term\Enums\TermWindowType;
use App\Models\Grading\GradeScale;
use App\Models\Grading\TermRecord;
use App\Models\Offering\Section;
use App\Models\Offering\SectionInstructor;
use App\Models\Person\Instructor;
use App\Models\Registration\Enrollment;
use App\Models\Term\Term;
use App\Models\Term\TermWindow;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    Notification::fake();
    GradeScale::factory()->korean()->create();
    $this->term = Term::factory()->published()->between(now()->subMonths(4)->toDateString(), now()->addWeek()->toDateString())->create();
    $this->window = TermWindow::factory()->for($this->term)->ofType(TermWindowType::GradeEntry)
        ->between(now()->subDay(), now()->addWeek())->create(['term_session_id' => $this->term->fullTermSession()->id]);
    // Relative evaluation (grades only) unless a test switches to absolute (scores).
    $this->section = Section::factory()->published()->inTerm($this->term)->create(['capacity' => 10, 'evaluation_method' => EvaluationMethod::Relative]);

    $instructor = Instructor::factory()->create();
    SectionInstructor::query()->create(['section_id' => $this->section->id, 'instructor_id' => $instructor->id, 'role' => 'primary']);
    $this->teacher = User::factory()->create(['person_id' => $instructor->person_id]);
    $this->registrar = User::factory()->withPermissions([Permission::GradesManage, Permission::GradeChangesApprove])->create();
    $this->clerk = User::factory()->withPermissions([Permission::GradeChangesRequest, Permission::GradeChangesApprove])->create();

    $this->enrollments = Enrollment::factory()->count(3)->inSection($this->section)->create();
    $this->section->forceFill(['enrolled_count' => 3])->save();
    $this->section->refresh();
});

function gradeAll(object $test, array $codes): array
{
    return collect($test->enrollments)->values()->mapWithKeys(fn (Enrollment $enrollment, int $index) => [$enrollment->id => ['grade_code' => $codes[$index]]])->all();
}

it('lets the instructor save drafts and submit, then locks the grades for them', function () {
    $save = app(SaveGrades::class);

    expect($save->save($this->section, gradeAll($this, ['A+', 'B0', null]), $this->teacher))->toBe(2);
    expect(fn () => $save->submit($this->section, $this->teacher))->toThrow(ValidationException::class);

    // Relative evaluation: grades only.
    expect(fn () => $save->save($this->section, [$this->enrollments[2]->id => ['score' => '91.5']], $this->teacher))->toThrow(ValidationException::class);
    $save->save($this->section, [$this->enrollments[2]->id => ['grade_code' => 'A0']], $this->teacher);
    expect($this->enrollments[2]->refresh())
        ->grade_code->toBe('A0')
        ->score->toBeNull()
        ->grade_points->toBe('4.00')
        ->grade_status->toBe(GradeStatus::Draft);

    expect($save->submit($this->section, $this->teacher))->toBe(3)
        ->and(fn () => $save->save($this->section, gradeAll($this, ['F', 'F', 'F']), $this->teacher))->toThrow(ValidationException::class)
        ->and($this->enrollments[0]->refresh()->grade_code)->toBe('A+');
});

it('turns scores into grades for absolute evaluation', function () {
    $this->section->forceFill(['evaluation_method' => EvaluationMethod::Absolute])->save();
    $save = app(SaveGrades::class);

    // Absolute evaluation takes scores; a letter grade with GPA value cannot be chosen.
    expect(fn () => $save->save($this->section, [$this->enrollments[0]->id => ['grade_code' => 'A0']], $this->teacher))->toThrow(ValidationException::class);

    $save->save($this->section, [$this->enrollments[0]->id => ['score' => '91.5'], $this->enrollments[1]->id => ['score' => '97']], $this->teacher);

    expect($this->enrollments[0]->refresh())->grade_code->toBe('A0')->grade_points->toBe('4.00')->score->toBe('91.50')
        ->and($this->enrollments[1]->refresh())->grade_code->toBe('A+')->grade_points->toBe('4.50');
});

it('lowers an absolute retake above the cap to the cap and keeps the score', function () {
    $this->section->forceFill(['evaluation_method' => EvaluationMethod::Absolute])->save();
    $this->enrollments[0]->forceFill(['is_retake' => true])->save();
    $settings = app(GradingSettings::class);
    $settings->retake_max_grade = 'B+';
    $settings->save();

    app(SaveGrades::class)->save($this->section, [$this->enrollments[0]->id => ['score' => '98']], $this->teacher);

    expect($this->enrollments[0]->refresh())->grade_code->toBe('B+')->score->toBe('98.00');
});

it('changes an absolute grade by a new score and refuses a bare grade', function () {
    $this->section->forceFill(['evaluation_method' => EvaluationMethod::Absolute])->save();
    app(SaveGrades::class)->save($this->section, collect($this->enrollments)->mapWithKeys(fn (Enrollment $enrollment) => [$enrollment->id => ['score' => '81']])->all(), $this->teacher);
    app(SaveGrades::class)->submit($this->section, $this->teacher);
    app(FinalizeGrades::class)->finalize($this->section, $this->registrar);
    $enrollment = $this->enrollments[0]->refresh();
    $changes = app(ManageGradeChanges::class);

    expect(fn () => $changes->request($enrollment, 'A0', 'Regrade', $this->teacher))->toThrow(ValidationException::class);

    $request = $changes->request($enrollment, null, 'Exam re-marked', $this->teacher, '92');
    expect($request)->to_grade_code->toBe('A0')->to_score->toBe('92.00');

    $changes->approve($request, $this->registrar);
    expect($enrollment->refresh())->grade_code->toBe('A0')->score->toBe('92.00');
});

it('keeps the retake cap through GPA and grade changes, and only for retakes', function () {
    $this->section->forceFill(['evaluation_method' => EvaluationMethod::Absolute])->save();
    [$retake, $first, $other] = [$this->enrollments[0], $this->enrollments[1], $this->enrollments[2]];
    $retake->forceFill(['is_retake' => true])->save();
    $settings = app(GradingSettings::class);
    $settings->retake_max_grade = 'B+';
    $settings->save();

    app(SaveGrades::class)->save($this->section, [$retake->id => ['score' => '98'], $first->id => ['score' => '98'], $other->id => ['score' => '70']], $this->teacher);

    // Same score: the retake is capped, the first attempt is not.
    expect($retake->refresh())->grade_code->toBe('B+')->grade_points->toBe('3.50')->score->toBe('98.00')
        ->and($first->refresh())->grade_code->toBe('A+')->grade_points->toBe('4.50');

    app(SaveGrades::class)->submit($this->section, $this->teacher);
    app(FinalizeGrades::class)->finalize($this->section, $this->registrar);

    // The GPA uses the capped grade points, never the score.
    expect(TermRecord::query()->where('student_record_id', $retake->student_record_id)->sole()->term_gpa)->toBe('3.50')
        ->and(TermRecord::query()->where('student_record_id', $first->student_record_id)->sole()->term_gpa)->toBe('4.50');

    // A changed score on the retake goes through the cap again.
    $changes = app(ManageGradeChanges::class);
    $request = $changes->request($retake->refresh(), null, 'Exam re-marked', $this->teacher, '100');
    expect($request->to_grade_code)->toBe('B+');
    $changes->approve($request, $this->registrar);

    expect($retake->refresh())->grade_code->toBe('B+')->score->toBe('100.00')
        ->and(TermRecord::query()->where('student_record_id', $retake->student_record_id)->sole()->term_gpa)->toBe('3.50');
});

it('does not change the evaluation method once grades are entered', function () {
    app(SaveGrades::class)->save($this->section, [$this->enrollments[0]->id => ['grade_code' => 'B0']], $this->teacher);

    expect(fn () => app(UpdateSection::class)->handle($this->section, ['evaluation_method' => 'absolute'], User::factory()->withPermissions([Permission::SectionsManage])->create()))
        ->toThrow(ValidationException::class, __('grading.errors.method_has_grades'));
});

it('refuses entry outside the grade entry window and by other instructors, but not by grade staff', function () {
    $this->window->forceFill(['closes_at' => now()->subMinute()])->save();
    $grades = gradeAll($this, ['A0', 'A0', 'A0']);

    expect(fn () => app(SaveGrades::class)->save($this->section, $grades, $this->teacher))->toThrow(ValidationException::class, __('grading.errors.entry_closed'))
        ->and(fn () => app(SaveGrades::class)->save($this->section, $grades, User::factory()->create()))->toThrow(AuthorizationException::class)
        ->and(app(SaveGrades::class)->save($this->section, $grades, $this->registrar))->toBe(3);
});

it('caps retakes at the configured grade and offers only P/NP for pass/fail courses', function () {
    $retake = $this->enrollments[1];
    $retake->forceFill(['is_retake' => true])->save();
    $settings = app(GradingSettings::class);
    $settings->retake_max_grade = 'A0';
    $settings->save();
    $save = app(SaveGrades::class);

    expect(fn () => $save->save($this->section, [$retake->id => ['grade_code' => 'A+']], $this->teacher))->toThrow(ValidationException::class)
        ->and($save->save($this->section, [$retake->id => ['grade_code' => 'A0'], $this->enrollments[0]->id => ['grade_code' => 'A+']], $this->teacher))->toBe(2);

    $this->section->version->forceFill(['grading_basis' => GradingBasis::PassFail])->save();

    expect(fn () => $save->save($this->section, [$this->enrollments[2]->id => ['grade_code' => 'A0']], $this->teacher))->toThrow(ValidationException::class)
        ->and($save->save($this->section, [$this->enrollments[2]->id => ['grade_code' => 'P']], $this->teacher))->toBe(1)
        ->and($this->enrollments[2]->refresh())->counts_in_gpa->toBeFalse()->is_passed->toBeTrue();
});

it('finalizes submitted grades into completed enrollments and term records', function () {
    Event::fake([GradesFinalized::class]);
    app(SaveGrades::class)->save($this->section, gradeAll($this, ['A+', 'B0', 'F']), $this->teacher);

    expect(fn () => app(FinalizeGrades::class)->finalize($this->section, $this->registrar))->toThrow(ValidationException::class);

    app(SaveGrades::class)->submit($this->section, $this->teacher);

    expect(fn () => app(FinalizeGrades::class)->finalize($this->section, $this->teacher))->toThrow(AuthorizationException::class)
        ->and(app(FinalizeGrades::class)->finalize($this->section, $this->registrar))->toBe(3);

    $failed = $this->enrollments[2]->refresh();
    expect($failed->status)->toBe(EnrollmentStatus::Completed)
        ->and($failed->grade_status)->toBe(GradeStatus::Finalized)
        ->and($failed->is_passed)->toBeFalse()
        ->and(TermRecord::query()->where('student_record_id', $failed->student_record_id)->sole())
        ->term_gpa->toBe('0.00')
        ->earned_credits->toBe('0.0');
    Event::assertDispatched(GradesFinalized::class);
});

it('returns submitted grades to the instructor with a reason', function () {
    app(SaveGrades::class)->save($this->section, gradeAll($this, ['A+', 'B0', 'C0']), $this->teacher);
    app(SaveGrades::class)->submit($this->section, $this->teacher);

    expect(app(FinalizeGrades::class)->return($this->section, $this->registrar, 'C0 looks like a typo'))->toBe(3)
        ->and($this->enrollments[2]->refresh()->grade_status)->toBe(GradeStatus::Draft);
    Notification::assertSentTo($this->teacher, GradesReturnedNotification::class);
});

it('changes a finalized grade only through a request decided by someone else', function () {
    app(SaveGrades::class)->save($this->section, gradeAll($this, ['B0', 'B0', 'B0']), $this->teacher);
    app(SaveGrades::class)->submit($this->section, $this->teacher);
    app(FinalizeGrades::class)->finalize($this->section, $this->registrar);
    $enrollment = $this->enrollments[0]->refresh();
    $changes = app(ManageGradeChanges::class);

    expect(fn () => $changes->request($enrollment, 'A0', 'Regrade', User::factory()->create()))->toThrow(AuthorizationException::class);

    $request = $changes->request($enrollment, 'A0', 'Missing assignment added', $this->teacher);

    expect(fn () => $changes->request($enrollment, 'A+', 'Again', $this->teacher))->toThrow(ValidationException::class)
        ->and(fn () => $changes->approve($request, $this->teacher))->toThrow(ValidationException::class);

    $changes->approve($request, $this->registrar, 'Checked the submission');

    expect($request->refresh())->status->toBe(GradeChangeStatus::Approved)->applied_at->not->toBeNull()
        ->and($enrollment->refresh())->grade_code->toBe('A0')->grade_points->toBe('4.00')
        ->and(TermRecord::query()->where('student_record_id', $enrollment->student_record_id)->sole()->term_gpa)->toBe('4.00');

    // Staff requests follow the same rule: the clerk cannot approve their own.
    $own = $changes->request($enrollment, 'B+', 'Late penalty', $this->clerk);
    expect(fn () => $changes->approve($own, $this->clerk))->toThrow(ValidationException::class, __('grading.errors.own_request'));
    $changes->reject($own, $this->registrar, 'The penalty does not apply');
    expect($own->refresh()->status)->toBe(GradeChangeStatus::Rejected)
        ->and($enrollment->refresh()->grade_code)->toBe('A0');
});
