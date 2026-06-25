<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContinueAthleteRegistration extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $firstName,
        public readonly string $loginUrl,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Weiter zur Sportler:innen-Anmeldung')
            ->greeting('Hallo '.$this->firstName)
            ->line('Du möchtest deine Anmeldung als Sportler:in fortsetzen.')
            ->line('Der unten stehende Link meldet dich sicher an und bringt dich zurück zur Anmeldung.')
            ->action('Anmeldung fortsetzen', $this->loginUrl)
            ->line('Falls du den Link nicht angefordert hast, kannst du diese E-Mail ignorieren oder uns kontaktieren.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
