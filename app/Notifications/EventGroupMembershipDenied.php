<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\ExternalUser;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

/** @api */
class EventGroupMembershipDenied extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $firstName,
        public readonly string $groupName,
        public readonly string $eventTitle,
        public readonly ?int $eventGroupId = null,
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
        $message = (new MailMessage)
            ->subject('Deine Gruppenanfrage wurde abgelehnt')
            ->greeting('Hallo '.$this->firstName)
            ->line('Deine Anfrage für die Gruppe "'.$this->groupName.'" beim Anlass '.$this->eventTitle.' wurde abgelehnt.')
            ->line('Du kannst dich jederzeit bei einer Gruppe erneut anfragen.');

        if ($this->eventGroupId !== null && $notifiable instanceof ExternalUser) {
            $message->action('Gruppe öffnen', URL::temporarySignedRoute('portal.login.uuid', now()->addMinutes(15), [
                'uuid' => $notifiable->uuid,
                'redirect' => 'group:'.$this->eventGroupId,
            ]));
        }

        return $message;
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
