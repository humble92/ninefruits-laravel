<?php

namespace App\Domain\Evaluation\Notifications;

use App\Models\Registration\Enrollment;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

/**
 * Asks a student to evaluate the courses still open.
 */
class EvaluationReminderNotification extends Notification
{
    /**
     * @param  Collection<int, Enrollment>  $enrollments  with section.course and version
     */
    public function __construct(public readonly Collection $enrollments) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject(__('evaluation.mail.reminder_subject'))
            ->greeting(__('security.email_code.mail_greeting'))
            ->line(__('evaluation.mail.reminder_intro'));

        foreach ($this->enrollments as $enrollment) {
            $message->line('- '.$enrollment->section->label().' '.$enrollment->version->title);
        }

        return $message->line(__('evaluation.mail.reminder_anonymous'))
            ->action(__('evaluation.mail.open_evaluations'), route('portal.evaluations'))
            ->salutation(__('security.email_code.mail_salutation', ['app' => config('app.name')]));
    }
}
