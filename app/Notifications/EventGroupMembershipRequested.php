<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** @api */
class EventGroupMembershipRequested extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $firstName,
        public readonly string $groupName,
        public readonly string $eventTitle,
        public readonly string $applicantPrivacyName,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Neue Gruppenanfrage')
            ->greeting('Hallo '.$this->firstName)
            ->line($this->applicantPrivacyName.' möchte der Gruppe "'.$this->groupName.'" beim Anlass '.$this->eventTitle.' beitreten.')
            ->line('Bitte prüfe die Anfrage im Portal.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
