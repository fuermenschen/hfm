<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AthleteNewDonation extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public readonly string $first_name,
        public readonly string $donor_name,
        public readonly string $public_id_string,
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
            ->subject('Ein:e Spender:in hat sich für dich registriert!')
            ->greeting(sprintf('Hallo %s,', $this->first_name))
            ->line(sprintf('Soeben hat sich %s als Spender:in für dich registriert.', $this->donor_name))
            ->line('Wenn du dich einloggst, siehst du, wer alles für dich spenden wird.')
            ->action('Login', route('show-athlete', $this->login_token))
            ->line('Vielen Dank, dass du so fleissig mithilfst, spenden zu sammeln! Wir freuen uns schon auf deine nächste:n Spender:innen von dir!')
            ->line('Vergiss nicht, deinen Code zu teilen: '.$this->public_id_string);
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
