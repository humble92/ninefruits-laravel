<?php

namespace App\Domain\Grading\Notifications;

use App\Models\Offering\Section;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

/**
 * Reminds an instructor of sections whose grades are not submitted yet.
 */
class MissingGradesNotification extends Notification
{
    /**
     * @param  Collection<int, Section>  $sections  with course and version
     */
    public function __construct(public readonly Collection $sections) {}

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
            ->subject(__('grading.mail.missing_subject'))
            ->greeting(__('security.email_code.mail_greeting'))
            ->line(__('grading.mail.missing_intro'));

        foreach ($this->sections as $section) {
            $message->line('- '.$section->label().' '.$section->version->title);
        }

        return $message->action(__('grading.mail.open_teaching'), route('portal.teaching'))
            ->salutation(__('security.email_code.mail_salutation', ['app' => config('app.name')]));
    }
}
