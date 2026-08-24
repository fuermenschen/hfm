<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\ExternalUser;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

/** @api */
class EventGroupMemberLeft extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $firstName,
        public readonly string $memberName,
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

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Ein Mitglied hat die Gruppe verlassen')
            ->greeting('Hallo '.$this->firstName)
            ->line($this->memberName.' hat die Gruppe "'.$this->groupName.'" beim Anlass '.$this->eventTitle.' verlassen.');

        if ($this->eventGroupId === null || ! $notifiable instanceof ExternalUser) {
            return $message;
        }

        $uuid = $notifiable->getAttribute('uuid');

        if (! is_string($uuid) || $uuid === '') {
            return $message;
        }

        $message->action('Gruppe öffnen', URL::temporarySignedRoute('portal.login.uuid', now()->addMinutes(15), [
            'uuid' => $uuid,
            'redirect' => 'group:'.$this->eventGroupId,
        ]));

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
