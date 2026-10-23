<?php

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
use App\Models\User;
use App\Support\Database\DatabaseSessionContext;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->section = Section::factory()->published()->create();
    $form = EvaluationForm::query()->create(['code' => 'T', 'name' => ['en' => 'Form']]);
    $question = EvaluationQuestion::query()->create(['evaluation_form_id' => $form->id, 'text' => ['en' => 'Q']]);
    $this->evaluation = TermEvaluation::query()->create(['term_id' => $this->section->term_id, 'evaluation_form_id' => $form->id]);

    $record = StudentRecord::factory()->create();
    $enrollment = Enrollment::factory()->inSection($this->section)->create(['student_record_id' => $record->id]);
    $this->student = User::factory()->create(['person_id' => $record->person_id]);
    EvaluationCompletion::query()->create(['enrollment_id' => $enrollment->id, 'completed_at' => now()]);
    $response = EvaluationResponse::query()->create(['term_id' => $this->section->term_id, 'section_id' => $this->section->id, 'evaluation_form_id' => $form->id]);
    EvaluationAnswer::query()->create(['evaluation_response_id' => $response->id, 'evaluation_question_id' => $question->id, 'value' => 4]);

    $instructor = Instructor::factory()->create();
    SectionInstructor::query()->create(['section_id' => $this->section->id, 'instructor_id' => $instructor->id, 'role' => 'primary']);
    $this->teacher = User::factory()->create(['person_id' => $instructor->person_id]);
});

function asEvaluationUser(User $user): void
{
    $context = app(DatabaseSessionContext::class);
    $context->useBaseline([]);
    $context->forUser($user->id);
}

it('never shows answers to students, only their own completion', function () {
    asEvaluationUser($this->student);

    expect(DB::table('evaluation_answers')->count())->toBe(0)
        ->and(DB::table('evaluation_responses')->count())->toBe(0)
        ->and(DB::table('evaluation_completions')->count())->toBe(1)
        ->and(databaseError(fn () => DB::table('evaluation_responses')->insert(['id' => (string) str()->uuid(), 'term_id' => $this->section->term_id, 'section_id' => $this->section->id, 'evaluation_form_id' => $this->evaluation->evaluation_form_id])))
        ->toContain('row-level security');
});

it('shows answers to the instructor only after publication', function () {
    asEvaluationUser($this->teacher);
    expect(DB::table('evaluation_answers')->count())->toBe(0)
        ->and(DB::table('evaluation_completions')->count())->toBe(0);

    DB::table('term_evaluations')->where('id', $this->evaluation->id)->update(['results_published_at' => now()]);

    expect(DB::table('evaluation_answers')->count())->toBe(1);
});
