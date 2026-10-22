<?php

namespace App\Domain\Admission\Notifications;

use App\Domain\Admission\Enums\ApplicationStatus;
use App\Models\Admission\Application;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells the applicant that a decision is available. The mail only points to the portal;
 * the decision itself is shown after signing in.
 */
class ApplicationDecisionNotification extends Notification
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
        $message = (new MailMessage)
            ->subject(__('admissions.mail.decision_subject', ['number' => $this->application->application_number]))
            ->greeting(__('security.email_code.mail_greeting'))
            ->line(__('admissions.mail.decision_intro', ['cycle' => $this->application->cycle->name, 'program' => $this->application->program->name]))
            ->action(__('admissions.mail.view_application'), route('portal.applications.show', $this->application));

        if ($this->application->status === ApplicationStatus::Admitted && $this->application->offer_expires_at !== null) {
            $message->line(__('admissions.mail.respond_by', ['date' => $this->application->offer_expires_at->isoFormat('LLL')]));
        }

        return $message->salutation(__('security.email_code.mail_salutation', ['app' => config('app.name')]));
    }
}
