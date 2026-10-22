<?php

namespace App\Domain\Security\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent immediately (not queued): the user is waiting on the challenge screen.
 */
class TwoFactorEmailCodeNotification extends Notification
{
    public function __construct(
        public readonly string $code,
        public readonly int $lifetimeMinutes,
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
        return (new MailMessage)
            ->subject(__('security.email_code.mail_subject'))
            ->greeting(__('security.email_code.mail_greeting'))
            ->salutation(__('security.email_code.mail_salutation', ['app' => config('app.name')]))
            ->line(__('security.email_code.mail_intro'))
            ->line('**'.$this->code.'**')
            ->line(__('security.email_code.mail_expiry', ['minutes' => $this->lifetimeMinutes]))
            ->line(__('security.email_code.mail_ignore'));
    }
}
