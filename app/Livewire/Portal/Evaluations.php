<?php

namespace App\Livewire\Portal;

use App\Domain\Evaluation\Actions\SubmitEvaluation;
use App\Domain\Evaluation\Services\EvaluationTasks;
use App\Domain\Person\Services\CurrentStudentRecord;
use App\Models\Evaluation\EvaluationCompletion;
use App\Models\Evaluation\EvaluationForm;
use App\Models\Evaluation\TermEvaluation;
use App\Models\Person\StudentRecord;
use App\Models\Registration\Enrollment;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Course evaluation for students (legacy counsel/student*.php): the courses open for
 * evaluation and the questionnaire. Answers are anonymous and final once submitted.
 */
#[Layout('components.layouts.portal')]
class Evaluations extends Component
{
    #[Url]
    public ?int $enrollmentId = null;

    /** @var array<int, string|int|null> by question id */
    public array $answers = [];

    /** @var list<string> */
    public array $messages = [];

    public string $messageTone = 'info';

    public function open(int $enrollmentId): void
    {
        $this->enrollmentId = $enrollmentId;
        $this->answers = [];
        $this->messages = [];
    }

    public function submit(EvaluationTasks $tasks): void
    {
        $enrollment = $tasks->open($this->record())->firstWhere('id', $this->enrollmentId) ?? abort(404);

        try {
            app(SubmitEvaluation::class)->handle($enrollment, $this->answers, $this->user());
            $this->enrollmentId = null;
            $this->answers = [];
            $this->messages = [__('evaluation.portal.submitted')];
            $this->messageTone = 'success';
        } catch (ValidationException $exception) {
            $this->messages = collect($exception->errors())->flatten()->all();
            $this->messageTone = 'error';
        }
    }

    public function render(EvaluationTasks $tasks): View
    {
        $record = $this->record();
        $open = $tasks->open($record);
        $done = EvaluationCompletion::query()->whereIn('enrollment_id', $open->pluck('id'))->pluck('enrollment_id')->all();
        $selected = $this->enrollmentId === null || in_array($this->enrollmentId, $done, true) ? null : $open->firstWhere('id', $this->enrollmentId);

        return view('livewire.portal.evaluations', [
            'open' => $open,
            'done' => $done,
            'selected' => $selected,
            'form' => $selected === null ? null : $this->form($selected),
        ])->title(__('evaluation.portal.title'));
    }

    private function form(Enrollment $enrollment): ?EvaluationForm
    {
        return TermEvaluation::query()->where('term_id', $enrollment->term_id)->with('form.questions')->first()?->form;
    }

    private function record(): StudentRecord
    {
        return app(CurrentStudentRecord::class)->get($this->user()) ?? abort(404);
    }

    private function user(): User
    {
        /** @var User */
        return Auth::user();
    }
}
