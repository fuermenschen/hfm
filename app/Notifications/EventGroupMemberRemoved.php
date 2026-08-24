<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\ExternalUser;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

/** @api */
class EventGroupMemberRemoved extends Notification
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

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Du wurdest aus einer Gruppe entfernt')
            ->greeting('Hallo '.$this->firstName)
            ->line('Du wurdest aus der Gruppe "'.$this->groupName.'" beim Anlass '.$this->eventTitle.' entfernt.');

        if (! $notifiable instanceof ExternalUser) {
            return $message;
        }

        $uuid = $notifiable->getAttribute('uuid');

        if (is_string($uuid) && $uuid !== '') {
            $message->action('Zum Portal', URL::temporarySignedRoute('portal.login.uuid', now()->addMinutes(15), [
                'uuid' => $uuid,
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
