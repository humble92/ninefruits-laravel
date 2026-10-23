<?php

namespace App\Livewire\Portal;

use App\Domain\Assessment\Actions\RegisterForAssessment;
use App\Domain\Assessment\Enums\AssessmentRegistrationStatus;
use App\Domain\Assessment\Services\AssessmentPassCheck;
use App\Domain\Person\Services\CurrentStudentRecord;
use App\Models\Assessment\AssessmentRegistration;
use App\Models\Assessment\AssessmentResult;
use App\Models\Assessment\AssessmentSession;
use App\Models\Person\StudentRecord;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Examinations for the student: sessions open for registration, own registrations,
 * published results, and the kinds the curriculum requires (legacy stu/cet*).
 */
#[Layout('components.layouts.portal')]
class MyAssessments extends Component
{
    public ?string $message = null;

    public string $messageTone = 'success';

    public function register(int $sessionId, RegisterForAssessment $register): void
    {
        $this->attempt(fn () => $register->register(AssessmentSession::query()->findOrFail($sessionId), $this->record(), $this->user()), __('assessment.portal.registered'));
    }

    public function cancel(int $registrationId, RegisterForAssessment $register): void
    {
        $registration = AssessmentRegistration::query()->where('student_record_id', $this->record()->id)->findOrFail($registrationId);
        $this->attempt(fn () => $register->cancel($registration, $this->user()), __('assessment.portal.cancelled'));
    }

    public function render(AssessmentPassCheck $check): View
    {
        $record = $this->record();
        $registrations = AssessmentRegistration::query()->where('student_record_id', $record->id)->with('session.type')->get();
        $open = AssessmentSession::query()->openForRegistration()->where('held_date', '>=', CarbonImmutable::today())
            ->whereHas('type', fn ($q) => $q->where('is_active', true))->with('type')->orderBy('held_date')->get()
            ->map(fn (AssessmentSession $session) => [
                'session' => $session,
                'registration' => $registrations->first(fn (AssessmentRegistration $r) => $r->assessment_session_id === $session->id && $r->status !== AssessmentRegistrationStatus::Cancelled),
            ]);

        return view('livewire.portal.my-assessments', [
            'open' => $open,
            'registrations' => $registrations->where('status', '!=', AssessmentRegistrationStatus::Cancelled)->sortByDesc(fn (AssessmentRegistration $r) => $r->session->held_date)->values(),
            // RLS hides session results until they are published.
            'results' => AssessmentResult::query()->where('student_record_id', $record->id)->with(['type', 'componentScores.component', 'media'])->orderByDesc('taken_date')->get(),
            'required' => $check->required($record),
        ])->title(__('assessment.portal.title'));
    }

    private function attempt(callable $callback, string $success): void
    {
        try {
            $callback();
            [$this->message, $this->messageTone] = [$success, 'success'];
        } catch (ValidationException $e) {
            [$this->message, $this->messageTone] = [implode(' ', array_merge(...array_values($e->errors()))), 'error'];
        }
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
