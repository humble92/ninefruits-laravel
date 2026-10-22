<?php

namespace App\Domain\Registration\Notifications;

use App\Domain\Registration\Enums\WaitlistStatus;
use App\Models\Registration\WaitlistEntry;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells a student they got the seat they waited for, or why they left the waitlist.
 */
class WaitlistOutcomeNotification extends Notification
{
    public function __construct(
        public readonly WaitlistEntry $entry,
        public readonly WaitlistStatus $status,
        public readonly ?string $note,
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
        $section = $this->entry->section->loadMissing(['course', 'version']);
        $label = $section->label().' '.$section->version->title;

        $message = (new MailMessage)
            ->subject(__('registration.mail.waitlist_'.$this->status->value.'_subject', ['section' => $label]))
            ->greeting(__('security.email_code.mail_greeting'))
            ->line(__('registration.mail.waitlist_'.$this->status->value.'_intro', ['section' => $label]));

        if ($this->note !== null) {
            $message->line($this->note);
        }

        return $message->action(__('registration.mail.open_registration'), route('portal.registration'))
            ->salutation(__('security.email_code.mail_salutation', ['app' => config('app.name')]));
    }
}
