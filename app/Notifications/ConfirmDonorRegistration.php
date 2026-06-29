<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ConfirmDonorRegistration extends Notification
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
            ->subject('Bitte bestätige deine Spende')
            ->greeting('Hallo '.$this->firstName)
            ->line('Deine Anmeldung als Spender:in ist bei uns eingegangen. Vielen Dank!')
            ->line('Bitte öffne den unten stehenden Link und bestätige deine Spende.')
            ->action('Spende bestätigen', $this->confirmationUrl)
            ->line('Erst nach der Bestätigung wird deine Spende aktiv.')
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
