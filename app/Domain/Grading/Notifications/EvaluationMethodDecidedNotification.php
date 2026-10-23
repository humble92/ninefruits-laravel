<?php

namespace App\Domain\Grading\Notifications;

use App\Domain\Grading\Enums\RequestStatus;
use App\Models\Grading\EvaluationMethodRequest;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells the instructor that their evaluation method request was approved or rejected.
 */
class EvaluationMethodDecidedNotification extends Notification
{
    public function __construct(
        public readonly EvaluationMethodRequest $request,
        public readonly int $draftsCleared,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $section = $this->request->section->loadMissing(['course', 'version']);
        $label = $section->label().' '.$section->version->title;
        $approved = $this->request->status === RequestStatus::Approved;

        $message = (new MailMessage)
            ->subject(__($approved ? 'grading.mail.method_approved_subject' : 'grading.mail.method_rejected_subject', ['section' => $label]))
            ->greeting(__('security.email_code.mail_greeting'))
            ->line(__($approved ? 'grading.mail.method_approved_intro' : 'grading.mail.method_rejected_intro', ['section' => $label, 'method' => $this->request->requested_method->label()]));

        if ($this->request->decision_note !== null) {
            $message->line($this->request->decision_note);
        }

        if ($approved && $this->draftsCleared > 0) {
            $message->line(__('grading.mail.method_drafts_cleared', ['count' => $this->draftsCleared]));
        }

        return $message->action(__('grading.mail.open_grade_entry'), route('portal.grades.entry', $section))
            ->salutation(__('security.email_code.mail_salutation', ['app' => config('app.name')]));
    }
}
