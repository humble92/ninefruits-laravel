<?php

namespace App\Domain\Grading\Notifications;

use App\Domain\Grading\Data\GradeDistribution;
use App\Models\Offering\Section;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells the reviewers (department heads, the academic office) that staff submitted a
 * section's relative grades outside the grade caps, and why.
 */
class GradeExceptionNotification extends Notification
{
    public function __construct(
        public readonly Section $section,
        public readonly User $submittedBy,
        public readonly string $reason,
        public readonly GradeDistribution $distribution,
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
        $section = $this->section->loadMissing(['course', 'version']);
        $label = $section->label().' '.$section->version->title;

        $message = (new MailMessage)
            ->subject(__('grading.mail.exception_subject', ['section' => $label]))
            ->greeting(__('security.email_code.mail_greeting'))
            ->line(__('grading.mail.exception_intro', ['section' => $label, 'user' => $this->submittedBy->email]))
            ->line(__('common.reason').': '.$this->reason);

        foreach ($this->distribution->violations as $violation) {
            $message->line('- '.$violation);
        }

        return $message->action(__('grading.mail.open_grade_entry'), route('portal.grades.entry', $section))
            ->salutation(__('security.email_code.mail_salutation', ['app' => config('app.name')]));
    }
}
