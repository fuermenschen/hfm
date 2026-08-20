<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** @api */
class EventGroupMembershipAccepted extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $firstName,
        public readonly string $groupName,
        public readonly string $eventTitle,
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
            ->subject('Deine Gruppenanfrage wurde angenommen')
            ->greeting('Hallo '.$this->firstName)
            ->line('Du bist jetzt Mitglied der Gruppe "'.$this->groupName.'" beim Anlass '.$this->eventTitle.'.');
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
