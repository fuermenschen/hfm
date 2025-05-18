<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DonationRegistered extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public readonly string $first_name,
        public readonly string $athlete_name,
        public readonly string $donation_id,
        public readonly string $login_token)
    {
        //
    }

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
            ->subject("Anmeldung als Spender:in für $this->athlete_name")
            ->greeting('Hallo '.$this->first_name)
            ->line("Du hast dich als Spender:in für $this->athlete_name angemeldet.")
            ->line('Bitte bestätige deine Anmeldung, indem du auf den folgenden Link klickst:')
            ->action('Spende bestätigen', route('verify-donation', [
                'login_token' => $this->login_token,
                'donation_id' => $this->donation_id,
            ]))
            ->line('Vielen Dank für deine Unterstützung!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
