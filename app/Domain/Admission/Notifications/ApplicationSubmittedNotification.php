<?php

namespace App\Domain\Admission\Notifications;

use App\Models\Admission\Application;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationSubmittedNotification extends Notification
{
    public function __construct(public readonly Application $application) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('admissions.mail.submitted_subject', ['number' => $this->application->application_number]))
            ->greeting(__('security.email_code.mail_greeting'))
            ->line(__('admissions.mail.submitted_intro', [
                'cycle' => $this->application->cycle->name,
                'program' => $this->application->program->name,
                'number' => $this->application->application_number,
            ]))
            ->action(__('admissions.mail.view_application'), route('portal.applications.show', $this->application))
            ->salutation(__('security.email_code.mail_salutation', ['app' => config('app.name')]));
    }
}
