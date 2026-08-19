<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ConfirmAthleteRegistration extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $firstName,
        public readonly string $confirmationUrl,
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
            ->subject('Bitte bestätige deine Sportler:innen-Anmeldung')
            ->greeting('Hallo '.$this->firstName)
            ->line('Deine Anmeldung als Sportler:in ist bei uns eingegangen. Vielen Dank!')
            ->line('Bitte öffne den unten stehenden Link und bestätige deine Registrierung. Damit stellen wir sicher, dass die Anmeldung wirklich von dir stammt.')
            ->action('Anmeldung bestätigen', $this->confirmationUrl)
            ->line('Erst nach der Bestätigung können dich Spender:innen später auswählen.')
            ->line('Falls du diese Anmeldung nicht gestartet hast, kontaktiere uns bitte.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
