<?php

namespace App\Domain\Evaluation\Actions;

use App\Domain\Evaluation\Enums\QuestionType;
use App\Domain\Evaluation\Services\EvaluationTasks;
use App\Domain\Evaluation\Settings\EvaluationSettings;
use App\Models\Evaluation\EvaluationAnswer;
use App\Models\Evaluation\EvaluationCompletion;
use App\Models\Evaluation\EvaluationQuestion;
use App\Models\Evaluation\EvaluationResponse;
use App\Models\Evaluation\TermEvaluation;
use App\Models\Registration\Enrollment;
use App\Models\User;
use App\Support\Database\DatabaseSessionContext;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * A student evaluates one of their courses (legacy counsel/student_addgo.php, which stored
 * the student number with each answer, took any course id and wrote rows one by one).
 *
 * The completion records who evaluated which enrollment; the response records the answers
 * for the section with no student, sequence or time. Both are written in one transaction
 * in the system context (row level security cannot check a student on an anonymous row).
 * One response per enrollment (unique completion); no change afterwards.
 */
class SubmitEvaluation
{
    public function __construct(
        private readonly EvaluationTasks $tasks,
        private readonly EvaluationSettings $settings,
        private readonly DatabaseSessionContext $context,
    ) {}

    /**
     * @param  array<int|string, int|string|null>  $answers  by question id: a scale value or a comment
     */
    public function handle(Enrollment $enrollment, array $answers, User $student): void
    {
        $enrollment->loadMissing(['studentRecord', 'section']);

        if ($student->person_id === null || $student->person_id !== $enrollment->studentRecord->person_id) {
            throw new AuthorizationException;
        }

        if (! $this->tasks->isOpen($enrollment)) {
            throw ValidationException::withMessages(['evaluation' => __('evaluation.errors.not_open')]);
        }

        if (EvaluationCompletion::query()->where('enrollment_id', $enrollment->id)->exists()) {
            throw ValidationException::withMessages(['evaluation' => __('evaluation.errors.already_done')]);
        }

        $termEvaluation = TermEvaluation::query()->where('term_id', $enrollment->term_id)->with('form.questions')->sole();
        $rows = $this->validated($termEvaluation, $answers);

        try {
            $this->context->asSystem('evaluation:submit', fn () => DB::transaction(function () use ($enrollment, $termEvaluation, $rows) {
                EvaluationCompletion::query()->create(['enrollment_id' => $enrollment->id, 'completed_at' => CarbonImmutable::now()]);
                $response = EvaluationResponse::query()->create([
                    'term_id' => $enrollment->term_id,
                    'section_id' => $enrollment->section_id,
                    'evaluation_form_id' => $termEvaluation->evaluation_form_id,
                ]);

                foreach ($rows as $row) {
                    EvaluationAnswer::query()->create(['evaluation_response_id' => $response->id, ...$row]);
                }
            }));
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['evaluation' => __('evaluation.errors.already_done')]);
        }
    }

    /**
     * @param  array<int|string, int|string|null>  $answers
     * @return list<array{evaluation_question_id: int, value: int|null, comment: string|null}>
     */
    private function validated(TermEvaluation $termEvaluation, array $answers): array
    {
        $form = $termEvaluation->form;
        $rows = [];
        $missing = 0;

        foreach ($form->questions as $question) {
            /** @var EvaluationQuestion $question */
            $answer = $answers[$question->id] ?? null;
            $answer = is_string($answer) ? trim($answer) : $answer;

            if ($answer === null || $answer === '') {
                $missing += $question->is_required ? 1 : 0;

                continue;
            }

            if ($question->question_type === QuestionType::Scale) {
                $value = filter_var($answer, FILTER_VALIDATE_INT);

                if ($value === false || $value < 1 || $value > $form->scale_points) {
                    throw ValidationException::withMessages(['answers' => __('evaluation.errors.invalid_answer')]);
                }

                $rows[] = ['evaluation_question_id' => $question->id, 'value' => $value, 'comment' => null];
            } else {
                $rows[] = ['evaluation_question_id' => $question->id, 'value' => null, 'comment' => mb_substr((string) $answer, 0, 2000)];
            }
        }

        if ($missing > 0) {
            throw ValidationException::withMessages(['answers' => __('evaluation.errors.missing_answers', ['count' => $missing])]);
        }

        $values = array_values(array_filter(array_column($rows, 'value'), fn (?int $value) => $value !== null));

        if ($this->settings->reject_uniform_answers && count($values) > 2 && count(array_unique($values)) === 1) {
            throw ValidationException::withMessages(['answers' => __('evaluation.errors.uniform_answers')]);
        }

        return $rows;
    }
}
