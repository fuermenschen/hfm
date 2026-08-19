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
            ->line('Bitte öffne den unten stehenden Link. Er bringt dich ins Portal, wo du deine Spende mit «Spende bestätigen» final bestätigst.')
            ->line('Mit deiner Bestätigung stellen wir sicher, dass die Spende wirklich von dir stammt und keine Rechnung an jemanden geschickt wird, der nicht spenden wollte.')
            ->action('Zum Portal', $this->confirmationUrl)
            ->line('Erst nach der Bestätigung wird deine Spende aktiv.')
            ->line('Falls du diese Anmeldung nicht gestartet hast, kontaktiere uns bitte.');
    }
}
