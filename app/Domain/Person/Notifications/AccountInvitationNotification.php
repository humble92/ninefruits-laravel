<?php

namespace App\Domain\Person\Notifications;

use Carbon\CarbonImmutable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountInvitationNotification extends Notification
{
    public function __construct(
        public readonly string $url,
        public readonly CarbonImmutable $expiresAt,
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
            ->subject(__('people.invitations.mail_subject', ['app' => config('app.name')]))
            ->greeting(__('security.email_code.mail_greeting'))
            ->line(__('people.invitations.mail_intro'))
            ->action(__('people.invitations.mail_action'), $this->url)
            ->line(__('people.invitations.mail_expiry', ['date' => $this->expiresAt->isoFormat('LL')]))
            ->salutation(__('security.email_code.mail_salutation', ['app' => config('app.name')]));
    }
}
