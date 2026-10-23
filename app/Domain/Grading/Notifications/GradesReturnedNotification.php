<?php

namespace App\Domain\Grading\Notifications;

use App\Models\Offering\Section;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells an instructor that staff returned the section's submitted grades, and why.
 */
class GradesReturnedNotification extends Notification
{
    public function __construct(
        public readonly Section $section,
        public readonly string $reason,
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

        return (new MailMessage)
            ->subject(__('grading.mail.returned_subject', ['section' => $label]))
            ->greeting(__('security.email_code.mail_greeting'))
            ->line(__('grading.mail.returned_intro', ['section' => $label]))
            ->line($this->reason)
            ->action(__('grading.mail.open_grade_entry'), route('portal.grades.entry', $section))
            ->salutation(__('security.email_code.mail_salutation', ['app' => config('app.name')]));
    }
}
