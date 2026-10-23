<?php

use App\Domain\Evaluation\Actions\ManageTermEvaluation;
use App\Domain\Evaluation\Actions\SubmitEvaluation;
use App\Domain\Evaluation\Services\EvaluationResults;
use App\Domain\Evaluation\Services\EvaluationTasks;
use App\Domain\Evaluation\Settings\EvaluationSettings;
use App\Domain\Registration\Enums\EnrollmentStatus;
use App\Domain\Term\Enums\TermWindowType;
use App\Livewire\Portal\Evaluations;
use App\Livewire\Portal\MyGrades;
use App\Models\Evaluation\EvaluationAnswer;
use App\Models\Evaluation\EvaluationCompletion;
use App\Models\Evaluation\EvaluationForm;
use App\Models\Evaluation\EvaluationQuestion;
use App\Models\Evaluation\EvaluationResponse;
use App\Models\Evaluation\TermEvaluation;
use App\Models\Offering\Section;
use App\Models\Offering\SectionInstructor;
use App\Models\Person\Instructor;
use App\Models\Person\StudentRecord;
use App\Models\Registration\Enrollment;
use App\Models\Term\Term;
use App\Models\Term\TermWindow;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    $this->term = Term::factory()->published()->between(now()->subMonths(4)->toDateString(), now()->addWeek()->toDateString())->create();
    $this->window = TermWindow::factory()->for($this->term)->ofType(TermWindowType::CourseEvaluation)
        ->between(now()->subDay(), now()->addWeek())->create(['term_session_id' => $this->term->fullTermSession()->id]);
    $this->form = EvaluationForm::query()->create(['code' => 'T', 'name' => ['en' => 'Test form'], 'scale_points' => 5]);
    $this->clear = EvaluationQuestion::query()->create(['evaluation_form_id' => $this->form->id, 'sort_order' => 1, 'text' => ['en' => 'Clear']]);
    $this->hard = EvaluationQuestion::query()->create(['evaluation_form_id' => $this->form->id, 'sort_order' => 2, 'text' => ['en' => 'Too hard'], 'is_reversed' => true]);
    $this->comment = EvaluationQuestion::query()->create(['evaluation_form_id' => $this->form->id, 'sort_order' => 3, 'text' => ['en' => 'Comments'], 'question_type' => 'text', 'is_required' => false]);
    $this->evaluation = TermEvaluation::query()->create(['term_id' => $this->term->id, 'evaluation_form_id' => $this->form->id, 'minimum_responses' => 2]);

    $this->section = Section::factory()->published()->inTerm($this->term)->create();
    $instructor = Instructor::factory()->create();
    SectionInstructor::query()->create(['section_id' => $this->section->id, 'instructor_id' => $instructor->id, 'role' => 'primary']);
    $this->teacher = User::factory()->create(['person_id' => $instructor->person_id]);
});

/**
 * @return array{0: Enrollment, 1: User}
 */
function evaluatingStudent(object $test, ?Section $section = null): array
{
    $record = StudentRecord::factory()->create();
    $enrollment = Enrollment::factory()->inSection($section ?? $test->section)->create(['student_record_id' => $record->id]);

    return [$enrollment, User::factory()->create(['person_id' => $record->person_id])];
}

function answers(object $test, int $clear = 4, int $hard = 2, ?string $comment = null): array
{
    return [$test->clear->id => $clear, $test->hard->id => $hard, $test->comment->id => $comment];
}

it('records who evaluated apart from what they answered', function () {
    [$enrollment, $student] = evaluatingStudent($this);

    app(SubmitEvaluation::class)->handle($enrollment, answers($this, comment: 'Great examples'), $student);

    $response = EvaluationResponse::query()->sole();
    expect(EvaluationCompletion::query()->sole()->enrollment_id)->toBe($enrollment->id)
        ->and(array_keys($response->getAttributes()))->toEqualCanonicalizing(['id', 'term_id', 'section_id', 'evaluation_form_id'])
        ->and($response->answers()->pluck('value', 'evaluation_question_id')->all())->toEqual([$this->clear->id => 4, $this->hard->id => 2, $this->comment->id => null])
        // Responses are not audited row by row: the data audit records the acting user.
        ->and(DB::table('audit.record_changes')->whereIn('table_name', ['evaluation_responses', 'evaluation_answers', 'evaluation_completions'])->count())->toBe(0);

    expect(fn () => app(SubmitEvaluation::class)->handle($enrollment, answers($this), $student))->toThrow(ValidationException::class, __('evaluation.errors.already_done'));
});

it('refuses evaluations outside the window, of others, incomplete or out of range', function () {
    [$enrollment, $student] = evaluatingStudent($this);
    $submit = app(SubmitEvaluation::class);

    expect(fn () => $submit->handle($enrollment, answers($this), User::factory()->create()))->toThrow(AuthorizationException::class)
        ->and(fn () => $submit->handle($enrollment, [$this->clear->id => 4], $student))->toThrow(ValidationException::class)
        ->and(fn () => $submit->handle($enrollment, answers($this, clear: 6), $student))->toThrow(ValidationException::class);

    $this->window->forceFill(['closes_at' => now()->subMinute()])->save();
    expect(fn () => $submit->handle($enrollment, answers($this), $student))->toThrow(ValidationException::class, __('evaluation.errors.not_open'))
        ->and(EvaluationResponse::query()->count())->toBe(0);
});

it('leaves out withdrawn courses and courses that are not evaluated', function () {
    [$withdrawn, $student] = evaluatingStudent($this);
    $withdrawn->forceFill(['status' => EnrollmentStatus::Withdrawn])->save();
    $internship = Section::factory()->published()->inTerm($this->term)->create();
    $internship->version->forceFill(['requires_evaluation' => false])->save();
    Enrollment::factory()->inSection($internship)->create(['student_record_id' => $withdrawn->student_record_id]);

    expect(app(EvaluationTasks::class)->open($withdrawn->studentRecord))->toBeEmpty();
});

it('refuses identical answers to every question when set', function () {
    $settings = app(EvaluationSettings::class);
    $settings->reject_uniform_answers = true;
    $settings->save();
    $third = EvaluationQuestion::query()->create(['evaluation_form_id' => $this->form->id, 'sort_order' => 4, 'text' => ['en' => 'Fair']]);
    [$enrollment, $student] = evaluatingStudent($this);

    expect(fn () => app(SubmitEvaluation::class)->handle($enrollment, answers($this, 5, 5) + [$third->id => 5], $student))
        ->toThrow(ValidationException::class, __('evaluation.errors.uniform_answers'));
});

it('hides the term grades until the evaluation is done or closed', function () {
    [$enrollment, $student] = evaluatingStudent($this);
    $enrollment->forceFill(['grade_status' => 'finalized', 'status' => 'completed', 'grade_code' => 'A0', 'grade_scale_id' => Enrollment::factory()->completed()->create()->grade_scale_id, 'grade_points' => 4, 'counts_in_gpa' => true, 'is_passed' => true])->save();
    $code = $enrollment->section->load('course')->course->code;
    $tasks = app(EvaluationTasks::class);

    expect($tasks->blockedTermIds($enrollment->studentRecord))->toBe([$this->term->id]);
    Livewire::actingAs($student)->test(MyGrades::class)->assertSee(__('evaluation.grades_hidden', ['term' => $this->term->name]))->assertDontSee($code);

    app(SubmitEvaluation::class)->handle($enrollment, answers($this), $student);
    expect($tasks->blockedTermIds($enrollment->studentRecord))->toBe([]);
    Livewire::actingAs($student)->test(MyGrades::class)->assertSee($code);

    // Unfinished, but the window closed: the grades show (the legacy lock never ended).
    [$other] = evaluatingStudent($this);
    expect($tasks->blockedTermIds($other->studentRecord))->toBe([$this->term->id])
        ->and($tasks->blockedTermIds($other->studentRecord, now()->addWeeks(2)->toImmutable()))->toBe([]);

    $settings = app(EvaluationSettings::class);
    $settings->grades_require_evaluation = false;
    $settings->save();
    expect(app(EvaluationTasks::class)->blockedTermIds($other->studentRecord))->toBe([]);
});

it('shows results only when published and with enough responses, reversing negative questions', function () {
    $results = app(EvaluationResults::class);
    [$first, $firstStudent] = evaluatingStudent($this);
    [$second, $secondStudent] = evaluatingStudent($this);
    evaluatingStudent($this); // did not answer
    app(SubmitEvaluation::class)->handle($first, answers($this, 5, 1, 'Clear and kind'), $firstStudent);

    expect($results->canView($this->section, $this->teacher))->toBeFalse()
        ->and($results->forSection($this->section))->shown->toBeFalse()->responses->toBe(1)->expected->toBe(3);

    app(ManageTermEvaluation::class)->publish($this->evaluation, null);
    expect($results->forSection($this->section)->shown)->toBeFalse(); // 1 of minimum 2

    app(SubmitEvaluation::class)->handle($second, answers($this, 3, 3), $secondStudent);
    $result = $results->forSection($this->section);

    expect($results->canView($this->section, $this->teacher))->toBeTrue()
        ->and($results->canView(Section::factory()->published()->inTerm($this->term)->create(), $this->teacher))->toBeFalse()
        ->and($result)->shown->toBeTrue()->ratePercent->toBe(66)
        ->and($result->questions[$this->clear->id]['mean'])->toBe(4.0)
        // Reversed: 1 counts as 5 and 3 as 3.
        ->and($result->questions[$this->hard->id]['mean'])->toBe(4.0)
        ->and($result->questions[$this->hard->id]['distribution'])->toBe([1 => 0, 2 => 0, 3 => 1, 4 => 0, 5 => 1])
        ->and($result->comments[$this->comment->id])->toBe(['Clear and kind'])
        ->and($result->overallMean)->toBe(4.0);

    $this->actingAs($this->teacher)->get(route('portal.evaluations.results', $this->section))->assertOk()->assertSee('Clear and kind');
    $this->actingAs($firstStudent)->get(route('portal.evaluations.results', $this->section))->assertForbidden();
});

it('lets the student evaluate from the portal', function () {
    [$enrollment, $student] = evaluatingStudent($this);

    Livewire::actingAs($student)->test(Evaluations::class)
        ->assertSee(__('evaluation.portal.evaluate'))
        ->call('open', $enrollment->id)
        ->assertSee('Too hard')
        ->set('answers.'.$this->clear->id, '4')
        ->set('answers.'.$this->hard->id, '2')
        ->call('submit')
        ->assertSet('messageTone', 'success')
        ->assertSee(__('evaluation.portal.done'));

    expect(EvaluationAnswer::query()->count())->toBe(2);
});
